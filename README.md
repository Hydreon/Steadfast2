# Steadfast2

## Getting Started
Steadfast is a project for backporting new Minecraft: Pocket Edition changes to older Pocketmine versions for better stability and performance, while retaining as many features from the new versions as possible. It's currently in production on Lifeboat Survival Games' main servers.

Things you might want to change before building:
  - Saving the server.log is disabled because it takes a lot of time to write to disk
  - The default MOTD for responding to MCPE server list queries is set in RaklibInterface (is currently set to Lifeboat Network)

#### Known bugs:

Things you'll want to change on your plugins:
   - Players don't fall out of the world naturally, you'll want to handle PlayerMoveEvent as needed to kill them
   

### Installing

#### Linux/MacOS
1)  `git clone git@github.com:Hydreon/Steadfast2.git` or `https://github.com/Hydreon/Steadfast2.git` in directory of your choosing. Or download and extract zip into directory of choosing. 

2) Navigate to `Steadfast2` directory via command line

3) Run command `./installer`

    If successful this will create a `bin` directory with a special Php7 build in it and a `start.sh` shell script
    
4) Running `./start` for the first time will take through a set up wizard where and create the 2 main config files for your server `pocketmine.yml` and `server.properties`    

#### Windows
Instructions coming soon... we suggest a Linux VM in the meantime.  We also suggest using Vagrant and picking a Ubuntu box -> [from here](https://atlas.hashicorp.com/boxes/search?utf8=%E2%9C%93&sort=&provider=&q=ubuntu)
    
 Notes: 
        
   - If using Vagrant have a config of `config.vm.network "public_network"` in the `Vagrantfile` should make your server discoverable from LAN. 
    
    
### Starting/Stopping Server
    
   Run command `./start` in from server root directory
    
   To stop the server `stop` in running servers terminal. (or CTRL + C should work).  

### Plugins

   Plugins go into the `plugins` in your servers root directory. Plugins will using come in the form of a `phar` but if you want to create plugins or run plugins from source then you need the DevTools plugin (DevToolvX.X.X.phar). You can get a copy from [Here](http://forums.pocketmine.net/plugins/devtools.515/) or other places as well. 
   
  To create a plugin phar from source code you will also need the DevTools plugin. 

### Building


To build a server phar, run the server with [DevTools](http://forums.pocketmine.net/plugins/devtools.515/) plugin installed then run `makeserver` in the server terminal. It'll drop a phar file in it's plugin directory.
