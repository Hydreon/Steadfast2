# Steadfast2

Steadfast is a project for backporting new Minecraft: Pocket Edition changes to older Pocketmine versions for better stability and performance, while retaining as many features from the new versions as possible. This release is compatible with production Minecraft PE 0.13.0 Alpha and based off Pocketmine-Soft-235. It's currently in production on Lifeboat Survival Games' main servers.

Things you might want to change before building:
  - Saving the server.log is disabled because it takes a lot of time to write to disk
  - The default MOTD for responding to MCPE server list queries is set in RaklibInterface

Known bugs:
   - Performance isn't as good as 1.4, some profiling needs to be done
   - Players can sometimes glitch into the ground
   - Player inventory can't be saved

Things you'll want to change on your plugins:
   - Players don't fall out of the world naturally, you'll want to handle PlayerMoveEvent as needed to kill them

To build, run the server with DevTools installed then run /makeserver. It'll drop a phar file in it's plugin directory.
