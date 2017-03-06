ClientPublicKey="$1"
ServerPrivateKey="$2"
CurrentDir="$3"

echo '-----BEGIN PUBLIC KEY-----' > $CurrentDir/client-pub.pem
echo $ClientPublicKey | cut -c1-64 >> $CurrentDir/client-pub.pem 
echo $ClientPublicKey | cut -c65-128 >> $CurrentDir/client-pub.pem 
echo $ClientPublicKey | cut -c129-196 >>$CurrentDir/client-pub.pem 
echo '-----END PUBLIC KEY-----' >> $CurrentDir/client-pub.pem

echo '-----BEGIN EC PRIVATE KEY-----' > $CurrentDir/server.pem
echo $ServerPrivateKey | cut -c1-64 >> $CurrentDir/server.pem 
echo $ServerPrivateKey | cut -c65-128 >> $CurrentDir/server.pem 
echo $ServerPrivateKey | cut -c129-196 >> $CurrentDir/server.pem 
echo $ServerPrivateKey | cut -c197-256 >> $CurrentDir/server.pem 
echo '-----END EC PRIVATE KEY-----' >> $CurrentDir/server.pem



openssl pkeyutl -derive -inkey "$CurrentDir/server.pem" -peerkey "$CurrentDir/client-pub.pem" -out $CurrentDir/result.dat
