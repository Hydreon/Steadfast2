 #  <img src="https://lbsg.net/wp-content/themes/lifeboat/images/lbsg-logo-sm.png" alt="LBSG logo" title="Aimeos" align="center" height="120" />
# Steadfast2: Minecraft Server Software

## Getting Started
 Check out our [wiki](https://github.com/Hydreon/Steadfast2/wiki) for guides on installation for [windows](https://github.com/Hydreon/Steadfast2/wiki/Installation-on-Windows), [MacOS and Linux.](https://github.com/Hydreon/Steadfast2/wiki/Installation-on-Linux-MacOS) You will also find information on how to [stop and start the server](https://github.com/Hydreon/Steadfast2/wiki/Starting-and-Stopping-the-server), along with a detailed guide on [how to use plugins](https://github.com/Hydreon/Steadfast2/wiki/How-to-use-plugins). Remember to check out our helpful [notes](https://github.com/Hydreon/Steadfast2/wiki/Notes)-- Be sure to read these before creating a Steadfast2 phar!
<br>
<br>
<br>
### About Steadfast
Steadfast is a project for backporting new Minecraft: Pocket Edition changes to older Pocketmine versions for better stability and performance, while retaining as many features from the new versions as possible. It's currently in production on Lifeboat Survival Games' main servers. Steadfast supports Pocket edition **and** Bedrock Edition (1.2+).
<br>
<br>
<br>
### How to use plugins
Plugins normally come in the form of a phar file: a php archive. Steadfast2 is coded in the PHP language, and so are the plugins. To run a plugin in the form of a phar file, place the plugin into the plugins directory of the server.

If the plugin is not in the form of a phar, something which happens often when downloading the plugin from its github repository, follow these steps:

Download the plugin <br>
Place it in the plugins directory <br>
Unzip it, so the file structure is as shown here: <br>
Steadfast2 Installation Directory  <br>
├── plugins <br>
ᅠᅠᅠ└── src <br>
ᅠᅠᅠᅠᅠᅠ└── PluginName <br>
ᅠᅠᅠᅠᅠᅠᅠᅠᅠ└── plugin.yml  <br>
4. Once the file structure is as shown above, you are good to go! Start the server and have fun!
<br>
<br>
<br>
### Installation on Linux and MacOS
`git clone git@github.com:Hydreon/Steadfast2.git` or `https://github.com/Hydreon/Steadfast2.git` in directory of your choosing. Or download and extract zip into directory of choosing. <br>
 <br>
Navigate to `Steadfast2` directory via command line <br>
 <br>
Run command `./installer` <br>
 <br>
If successful this will create a `bin` directory with a special Php7 build in it and a `start.sh` shell script <br>
 <br>
Running `./start` for the first time will take through a set up wizard where and create the 2 main config files for your server `pocketmine.yml` and `server.properties` <br>

#### Linux VM Notes:

If using Vagrant have a config of `config.vm.network "public_network"` in the `Vagrantfile` should make your server discoverable from LAN.
<br>
<br>
<br>
### Installation on Windows

1. Install [Visual C++ Studio 2015- x64 or x86](https://www.microsoft.com/en-us/download/confirmation.aspx?id=48145)
2. Download Steadfast´s windows launcher from [HERE](https://github.com/Inactive-to-Reactive/Windows-PHP7-Launcher/archive/master.zip) (Originally from ImagicalMine, it works still)
3. Download Steadfast2's source from [HERE](https://github.com/Hydreon/Steadfast2/archive/master.zip)
4. Extract the Steadfast zip in the same directory as the windows launcher
5. Run the "start.cmd" file
6. After setup, type "makeserver" to create a steadfast phar [NOTE: Before doing this, click me and read this](https://github.com/Hydreon/Steadfast2/wiki/Notes)
7. Type "stop" to safely stop the server, then close the window
8. Navigate to the plugins directory, then to the DevTools directory and copy the phar file that is there into the root directory (where Steadfast2 was installed). Name the phar "Software.phar"
9. Run the "start.cmd" file again
10. Have fun with Steadfast2!

#### If you are seeing the error below, it means that you incorrectly installed vcredist.
'/usr/bin/php/php.exe: error while loading shared libraries: VCRUNTIME140.dll: cannot open shared object file: No such file or directory bin\php\php.exe: Exit 127'

(means you have installed the x86 version, therefore you need the x64 version of Visual Studio C++ Redist. or if you have installed the x86 version, you need the x64 Visual Studio C++ Redist. version)

#### How to fix:
Revisit the link in step 1. If you originally downloaded x86, now download x64. If you originally downloaded x64, now download x86.
Install, and try again, it should now work. Contact @TheRoyalBlock if this still does not work.
<br>
<br>
<br>
### Things you'll want to do before building a phar: 
Saving the server.log is disabled by default, but many people would like to change this.

The default MOTD for responding to MCPE server list queries is set in RaklibInterface (is currently set to Lifeboat Network). To access RaklibInterface, navigate to the Steadfast directory, then go to src-->pocketmine-->network-->RaklibInterface.php and change the motd
<br>
<br>
<br>
### Starting the server on windows
Navigate to the directory in which Steadfast is installed        
Run the `start.cmd` file to start

### Stopping the server on windows
Open the server window and type `stop`
<br>
<br>
<br>
### Starting the server on Linux/MacOS
Run command ./start in from server root directory

### Stopping the server on Linux/MacOS
To stop the server stop in running servers terminal. (or CTRL + C should work).
<br>
<br>
<br>
### Known bugs:
Visit the [issues page](https://github.com/Hydreon/Steadfast2/issues) for a list of known bugs in Steadfast2.
<br>
<br>
<br>
### Things you'll want to change on your plugins:
   - Players don't fall out of the world naturally, you'll want to handle PlayerMoveEvent as needed to kill them
