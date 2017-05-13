#!/bin/bash

CHANNEL="stable"
BRANCH="master"
NAME="PocketMine-MP"
BUILD_URL=""

LINUX_32_BUILD="PHP_7.0.6_x86_Linux"
LINUX_64_BUILD="PHP_7.0.6_x86-64_Linux"
#CENTOS_32_BUILD="PHP_5.6.2_x86_CentOS"
#CENTOS_64_BUILD="PHP_5.6.2_x86-64_CentOS"
MAC_32_BUILD="PHP_7.0.3_x86_MacOS"
MAC_64_BUILD="PHP_7.0.3_x86-64_MacOS"
RPI_BUILD="PHP_7.0.6_ARM_Raspbian_hard"
ARMV7_BUILD="PHP_7.0.0RC3_ARMv7"
AND_BUILD="PHP_7.0.6_ARMv7_Android"
IOS_BUILD="PHP_5.5.13_ARMv6_iOS"
update=off
forcecompile=off
alldone=no
checkRoot=on
XDEBUG="off"

INSTALL_DIRECTORY="./"

IGNORE_CERT="yes"

while getopts "rxucid:v:t:" opt; do
  case $opt in
    r)
	  checkRoot=off
      ;;
    x)
	  XDEBUG="on"
	  echo "[+] Enabling xdebug"
      ;;
    u)
	  update=on
      ;;
    c)
	  forcecompile=on
      ;;
	d)
	  INSTALL_DIRECTORY="$OPTARG"
      ;;
	i)
	  IGNORE_CERT="no"
      ;;
	v)
	  CHANNEL="$OPTARG"
      ;;
	t)
	  BUILD_URL="$OPTARG"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
	  exit 1
      ;;
  esac
done


#Needed to use aliases
shopt -s expand_aliases
type wget > /dev/null 2>&1
if [ $? -eq 0 ]; then
	if [ "$IGNORE_CERT" == "yes" ]; then
		alias download_file="wget --no-check-certificate -q -O -"
	else
		alias download_file="wget -q -O -"
	fi
else
	type curl >> /dev/null 2>&1
	if [ $? -eq 0 ]; then
		if [ "$IGNORE_CERT" == "yes" ]; then
			alias download_file="curl --insecure --silent --location"
		else
			alias download_file="curl --silent --location"
		fi
	else
		echo "error, curl or wget not found"
	fi
fi

if [ "$checkRoot" == "on" ]; then
	if [ "$(id -u)" == "0" ]; then
	   echo "This script is running as root, this is discouraged."
	   echo "It is recommended to run it as a normal user as it doesn't need further permissions."
	   echo "If you want to run it as root, add the -r flag."
	   exit 1
	fi
fi

if [ "$CHANNEL" == "soft" ]; then
	NAME="PocketMine-Soft"
fi

ENABLE_GPG="no"
PUBLICKEY_URL="http://cdn.pocketmine.net/pocketmine.asc"
PUBLICKEY_FINGERPRINT="20D377AFC3F7535B3261AA4DCF48E7E52280B75B"
PUBLICKEY_LONGID="${PUBLICKEY_FINGERPRINT: -16}"
GPG_KEYSERVER="pgp.mit.edu"

function check_signature {
	echo "[*] Checking signature of $1"
	"$GPG_BIN" --keyserver "$GPG_KEYSERVER" --keyserver-options auto-key-retrieve=1 --trusted-key $PUBLICKEY_LONGID --verify "$1.sig" "$1"
	if [ $? -eq 0 ]; then
		echo "[+] Signature valid and checked!"
	else
		"$GPG_BIN" --refresh-keys > /dev/null 2>&1
		echo "[!] Invalid signature! Please check for file corruption or a wrongly imported public key (signed by $PUBLICKEY_FINGERPRINT)"
		exit 1
	fi	
}

if [[ "$BUILD_URL" != "" && "$CHANNEL" == "custom" ]]; then
	BASE_VERSION="custom"
	VERSION="custom"
	BUILD="unknown"
	API_VERSION="unknown"
	VERSION_DATE_STRING="unknown"
	ENABLE_GPG="no"
	VERSION_DOWNLOAD="$BUILD_URL"
else

VERSION_DATA=$(download_file "http://www.pocketmine.net/api/?channel=$CHANNEL")

VERSION=$(echo "$VERSION_DATA" | grep '"version"' | cut -d ':' -f2- | tr -d ' ",')
BUILD=$(echo "$VERSION_DATA" | grep build | cut -d ':' -f2- | tr -d ' ",')
API_VERSION=$(echo "$VERSION_DATA" | grep api_version | cut -d ':' -f2- | tr -d ' ",')
VERSION_DATE=$(echo "$VERSION_DATA" | grep '"date"' | cut -d ':' -f2- | tr -d ' ",')
VERSION_DOWNLOAD=$(echo "$VERSION_DATA" | grep '"download_url"' | cut -d ':' -f2- | tr -d ' ",')

if [ "$(uname -s)" == "Darwin" ]; then
	BASE_VERSION=$(echo "$VERSION" | sed -E 's/([A-Za-z0-9_\.]*).*/\1/')
	VERSION_DATE_STRING=$(date -j -f "%s" $VERSION_DATE)
else
	BASE_VERSION=$(echo "$VERSION" | sed -r 's/([A-Za-z0-9_\.]*).*/\1/')
	VERSION_DATE_STRING=$(date --date="@$VERSION_DATE")
fi

GPG_SIGNATURE=$(echo "$VERSION_DATA" | grep '"signature_url"' | cut -d ':' -f2- | tr -d ' ",')

if [ "$GPG_SIGNATURE" != "" ]; then
	ENABLE_GPG="yes"
fi

if [ "$VERSION" == "" ]; then
	echo "[!] Couldn't get the latest $NAME version"
	exit 1
fi

GPG_BIN=""

if [ "$ENABLE_GPG" == "yes" ]; then
	type gpg > /dev/null 2>&1
	if [ $? -eq 0 ]; then
		GPG_BIN="gpg"
	else
		type gpg2 > /dev/null 2>&1
		if [ $? -eq 0 ]; then
			GPG_BIN="gpg2"
		fi
	fi
	
	if [ "$GPG_BIN" != "" ]; then
		gpg --fingerprint $PUBLICKEY_FINGERPRINT > /dev/null 2>&1
		if [ $? -ne 0 ]; then
			download_file $PUBLICKEY_URL | gpg --trusted-key $PUBLICKEY_LONGID --import
			gpg --fingerprint $PUBLICKEY_FINGERPRINT > /dev/null 2>&1
			if [ $? -ne 0 ]; then
				gpg --trusted-key $PUBLICKEY_LONGID --keyserver "$GPG_KEYSERVER" --recv-key $PUBLICKEY_FINGERPRINT
			fi
		fi
	else
		ENABLE_GPG="no"
	fi
fi

fi

echo "[*] Found $NAME $BASE_VERSION (build $BUILD) using API $API_VERSION"
echo "[*] This $CHANNEL build was released on $VERSION_DATE_STRING"

if [ "$ENABLE_GPG" == "yes" ]; then
	echo "[+] The build was signed, will check signature"
elif [ "$GPG_SIGNATURE" == "" ]; then
	if [[ "$CHANNEL" == "beta" ]] || [[ "$CHANNEL" == "stable" ]]; then
		echo "[-] This channel should have a signature, none found"
	fi
fi



if [ "$CHANNEL" == "soft" ]; then
	download_file "https://raw.githubusercontent.com/PocketMine/PocketMine-Soft/${BRANCH}/resources/start.sh" > start.sh
else
	download_file "https://raw.githubusercontent.com/pmmp/PocketMine-MP/${BRANCH}/start.sh" > start.sh
fi

download_file "https://raw.githubusercontent.com/pmmp/php-build-scripts/${BRANCH}/compile.sh" > compile.sh


chmod +x compile.sh
chmod +x start.sh

echo " done!"

if [ "$update" == "on" ]; then
	echo "[3/3] Skipping PHP recompilation due to user request"
else
	echo " detecting if build is available..."
	if [ "$forcecompile" == "off" ] && [ "$(uname -s)" == "Darwin" ]; then
		set +e
		UNAME_M=$(uname -m)
		IS_IOS=$(expr match $UNAME_M 'iP[a-zA-Z0-9,]*' 2> /dev/null)
		set -e
		if [[ "$IS_IOS" -gt 0 ]]; then
			rm -r -f bin/ >> /dev/null 2>&1
			echo -n "[3/3] iOS PHP build available, downloading $IOS_BUILD.tar.gz..."
			download_file "https://dl.bintray.com/pocketmine/PocketMine/$IOS_BUILD.tar.gz" | tar -zx > /dev/null 2>&1
			chmod +x ./bin/php7/bin/*
			echo -n " checking..."
			if [ "$(./bin/php7/bin/php -r 'echo 1;' 2>/dev/null)" == "1" ]; then
				echo -n " regenerating php.ini..."
				TIMEZONE=$(date +%Z)
				echo "" > "./bin/php7/bin/php.ini"
				#UOPZ_PATH="$(find $(pwd) -name uopz.so)"
				#echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php7/bin/php.ini"
				echo "date.timezone=$TIMEZONE" >> "./bin/php7/bin/php.ini"
				echo "short_open_tag=0" >> "./bin/php7/bin/php.ini"
				echo "asp_tags=0" >> "./bin/php7/bin/php.ini"
				echo "phar.readonly=0" >> "./bin/php7/bin/php.ini"
				echo "phar.require_hash=1" >> "./bin/php7/bin/php.ini"
				echo "zend.assertions=-1" >> "./bin/php7/bin/php.ini"
				echo " done"
				alldone=yes
			else
				echo " invalid build detected"
			fi
		else
			rm -r -f bin/ >> /dev/null 2>&1
			if [ `getconf LONG_BIT` == "64" ]; then
				echo -n "[3/3] MacOS 64-bit PHP build available, downloading $MAC_64_BUILD.tar.gz..."
				MAC_BUILD="$MAC_64_BUILD"
			else
				echo -n "[3/3] MacOS 32-bit PHP build available, downloading $MAC_32_BUILD.tar.gz..."
				MAC_BUILD="$MAC_32_BUILD"
			fi
			download_file "https://dl.bintray.com/pocketmine/PocketMine/$MAC_BUILD.tar.gz" | tar -zx > /dev/null 2>&1
			chmod +x ./bin/php7/bin/*
			echo -n " checking..."
			if [ "$(./bin/php7/bin/php -r 'echo 1;' 2>/dev/null)" == "1" ]; then
				echo -n " regenerating php.ini..."
				TIMEZONE=$(date +%Z)
				#OPCACHE_PATH="$(find $(pwd) -name opcache.so)"
				XDEBUG_PATH="$(find $(pwd) -name xdebug.so)"
				echo "" > "./bin/php7/bin/php.ini"
				#UOPZ_PATH="$(find $(pwd) -name uopz.so)"
				#echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php7/bin/php.ini"
				#echo "zend_extension=\"$OPCACHE_PATH\"" >> "./bin/php7/bin/php.ini"
				if [ "$XDEBUG" == "on" ]; then
					echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php7/bin/php.ini"
				fi
				echo "opcache.enable=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.enable_cli=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.save_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.load_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.fast_shutdown=0" >> "./bin/php7/bin/php.ini"
				echo "opcache.max_accelerated_files=4096" >> "./bin/php7/bin/php.ini"
				echo "opcache.interned_strings_buffer=8" >> "./bin/php7/bin/php.ini"
				echo "opcache.memory_consumption=128" >> "./bin/php7/bin/php.ini"
				echo "opcache.optimization_level=0xffffffff" >> "./bin/php7/bin/php.ini"
				echo "date.timezone=$TIMEZONE" >> "./bin/php7/bin/php.ini"
				echo "short_open_tag=0" >> "./bin/php7/bin/php.ini"
				echo "asp_tags=0" >> "./bin/php7/bin/php.ini"
				echo "phar.readonly=0" >> "./bin/php7/bin/php.ini"
				echo "phar.require_hash=1" >> "./bin/php7/bin/php.ini"
				echo "zend.assertions=-1" >> "./bin/php7/bin/php.ini"
				echo " done"
				alldone=yes
			else
				echo " invalid build detected"
			fi
		fi
	else
		grep -q BCM2708 /proc/cpuinfo > /dev/null 2>&1
		IS_RPI=$?
		grep -q sun7i /proc/cpuinfo > /dev/null 2>&1
		IS_BPI=$?
		uname -m | grep -q armv7 > /dev/null 2>&1
		IS_ARMV7=$?
		if ([ "$IS_RPI" -eq 0 ] || [ "$IS_BPI" -eq 0 ]) && [ "$forcecompile" == "off" ]; then
			rm -r -f bin/ >> /dev/null 2>&1
			echo -n "[3/3] Raspberry Pi PHP build available, downloading $RPI_BUILD.tar.gz..."
			download_file "https://dl.bintray.com/pocketmine/PocketMine/$RPI_BUILD.tar.gz" | tar -zx > /dev/null 2>&1
			chmod +x ./bin/php7/bin/*
			echo -n " checking..."
			if [ "$(./bin/php7/bin/php -r 'echo 1;' 2>/dev/null)" == "1" ]; then
				echo -n " regenerating php.ini..."
				TIMEZONE=$(date +%Z)
				#OPCACHE_PATH="$(find $(pwd) -name opcache.so)"
				if [ "$XDEBUG" == "on" ]; then
					echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php7/bin/php.ini"
				fi
				echo "" > "./bin/php7/bin/php.ini"
				#UOPZ_PATH="$(find $(pwd) -name uopz.so)"
				#echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php7/bin/php.ini"
				#echo "zend_extension=\"$OPCACHE_PATH\"" >> "./bin/php7/bin/php.ini"
				if [ "$XDEBUG" == "on" ]; then
					echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php7/bin/php.ini"
				fi
				echo "opcache.enable=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.enable_cli=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.save_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.load_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.fast_shutdown=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.max_accelerated_files=4096" >> "./bin/php7/bin/php.ini"
				echo "opcache.interned_strings_buffer=8" >> "./bin/php7/bin/php.ini"
				echo "opcache.memory_consumption=128" >> "./bin/php7/bin/php.ini"
				echo "opcache.optimization_level=0xffffffff" >> "./bin/php7/bin/php.ini"
				echo "date.timezone=$TIMEZONE" >> "./bin/php7/bin/php.ini"
				echo "short_open_tag=0" >> "./bin/php7/bin/php.ini"
				echo "asp_tags=0" >> "./bin/php7/bin/php.ini"
				echo "phar.readonly=0" >> "./bin/php7/bin/php.ini"
				echo "phar.require_hash=1" >> "./bin/php7/bin/php.ini"
				echo "zend.assertions=-1" >> "./bin/php7/bin/php.ini"
				echo " done"
				alldone=yes
			else
				echo " invalid build detected"
			fi
		elif [ "$IS_ARMV7" -eq 0 ] && [ "$forcecompile" == "off" ]; then
			rm -r -f bin/ >> /dev/null 2>&1
			echo -n "[3/3] ARMv7 PHP build available, downloading $ARMV7_BUILD.tar.gz..."
			download_file "https://dl.bintray.com/pocketmine/PocketMine/$ARMV7_BUILD.tar.gz" | tar -zx > /dev/null 2>&1
			chmod +x ./bin/php7/bin/*
			echo -n " checking..."
			if [ "$(./bin/php7/bin/php -r 'echo 1;' 2>/dev/null)" == "1" ]; then
				echo -n " regenerating php.ini..."
				#OPCACHE_PATH="$(find $(pwd) -name opcache.so)"
				XDEBUG_PATH="$(find $(pwd) -name xdebug.so)"
				echo "" > "./bin/php7/bin/php.ini"
				#UOPZ_PATH="$(find $(pwd) -name uopz.so)"
				#echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php7/bin/php.ini"
				#echo "zend_extension=\"$OPCACHE_PATH\"" >> "./bin/php7/bin/php.ini"
				if [ "$XDEBUG" == "on" ]; then
					echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php7/bin/php.ini"
				fi
				echo "opcache.enable=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.enable_cli=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.save_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.load_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.fast_shutdown=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.max_accelerated_files=4096" >> "./bin/php7/bin/php.ini"
				echo "opcache.interned_strings_buffer=8" >> "./bin/php7/bin/php.ini"
				echo "opcache.memory_consumption=128" >> "./bin/php7/bin/php.ini"
				echo "opcache.optimization_level=0xffffffff" >> "./bin/php7/bin/php.ini"
				echo "date.timezone=$TIMEZONE" >> "./bin/php7/bin/php.ini"
				echo "short_open_tag=0" >> "./bin/php7/bin/php.ini"
				echo "asp_tags=0" >> "./bin/php7/bin/php.ini"
				echo "phar.readonly=0" >> "./bin/php7/bin/php.ini"
				echo "phar.require_hash=1" >> "./bin/php7/bin/php.ini"
				echo "zend.assertions=-1" >> "./bin/php7/bin/php.ini"
				echo " done"
				alldone=yes
			else
				echo " invalid build detected"
			fi
		elif [ "$forcecompile" == "off" ] && [ "$(uname -s)" == "Linux" ]; then
			rm -r -f bin/ >> /dev/null 2>&1
			
			#if [[ "$(cat /etc/redhat-release 2>/dev/null)" == *CentOS* ]]; then
				#if [ `getconf LONG_BIT` = "64" ]; then
				#	echo -n "[3/3] CentOS 64-bit PHP build available, downloading $CENTOS_64_BUILD.tar.gz..."
				#	LINUX_BUILD="$CENTOS_64_BUILD"
				#else
				#	echo -n "[3/3] CentOS 32-bit PHP build available, downloading $CENTOS_32_BUILD.tar.gz..."
				#	LINUX_BUILD="$CENTOS_32_BUILD"
				#fi
			#else
				if [ `getconf LONG_BIT` = "64" ]; then
					echo -n "[3/3] Linux 64-bit PHP build available, downloading $LINUX_64_BUILD.tar.gz..."
					LINUX_BUILD="$LINUX_64_BUILD"
				else
					echo -n "[3/3] Linux 32-bit PHP build available, downloading $LINUX_32_BUILD.tar.gz..."
					LINUX_BUILD="$LINUX_32_BUILD"
				fi
			#fi
			
			download_file "https://dl.bintray.com/pocketmine/PocketMine/$LINUX_BUILD.tar.gz" | tar -zx > /dev/null 2>&1
			chmod +x ./bin/php7/bin/*
			echo -n " checking..."
			if [ "$(./bin/php7/bin/php -r 'echo 1;' 2>/dev/null)" == "1" ]; then
				echo -n " regenerating php.ini..."
				#OPCACHE_PATH="$(find $(pwd) -name opcache.so)"
				XDEBUG_PATH="$(find $(pwd) -name xdebug.so)"
				echo "" > "./bin/php7/bin/php.ini"
				#UOPZ_PATH="$(find $(pwd) -name uopz.so)"
				#echo "zend_extension=\"$UOPZ_PATH\"" >> "./bin/php7/bin/php.ini"
				#echo "zend_extension=\"$OPCACHE_PATH\"" >> "./bin/php7/bin/php.ini"
				if [ "$XDEBUG" == "on" ]; then
					echo "zend_extension=\"$XDEBUG_PATH\"" >> "./bin/php7/bin/php.ini"
				fi
				echo "opcache.enable=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.enable_cli=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.save_comments=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.fast_shutdown=1" >> "./bin/php7/bin/php.ini"
				echo "opcache.max_accelerated_files=4096" >> "./bin/php7/bin/php.ini"
				echo "opcache.interned_strings_buffer=8" >> "./bin/php7/bin/php.ini"
				echo "opcache.memory_consumption=128" >> "./bin/php7/bin/php.ini"
				echo "opcache.optimization_level=0xffffffff" >> "./bin/php7/bin/php.ini"
				echo "date.timezone=$TIMEZONE" >> "./bin/php7/bin/php.ini"
				echo "short_open_tag=0" >> "./bin/php7/bin/php.ini"
				echo "asp_tags=0" >> "./bin/php7/bin/php.ini"
				echo "phar.readonly=0" >> "./bin/php7/bin/php.ini"
				echo "phar.require_hash=1" >> "./bin/php7/bin/php.ini"
				echo "zend.assertions=-1" >> "./bin/php7/bin/php.ini"
				echo " done"
				alldone=yes
			else
				echo " invalid build detected, please upgrade your OS"
			fi
		fi
		if [ "$alldone" == "no" ]; then
			set -e
			echo "[3/3] no build found, compiling PHP automatically"
			exec "./compile.sh"
		fi
	fi
fi

rm compile.sh

echo "[*] Everything done! Run ./start.sh to start $NAME"
exit 0
