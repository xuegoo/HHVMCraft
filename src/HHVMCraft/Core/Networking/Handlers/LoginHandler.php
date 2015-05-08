<?php
/**
 * This file is part of HHVMCraft - a Minecraft server implemented in PHP
 *
 * @copyright Andrew Vy 2015
 * @license MIT <https://github.com/andrewvy/HHVMCraft/blob/master/LICENSE.md>
 */
namespace HHVMCraft\Core\Networking\Handlers;

use HHVMCraft\Core\Networking\Packets\HandshakeResponsePacket;
use HHVMCraft\Core\Networking\Packets\LoginResponsePacket;
use HHVMCraft\Core\Networking\Packets\SetPlayerPositionPacket;
use HHVMCraft\Core\Networking\Packets\SpawnPositionPacket;
use HHVMCraft\Core\Networking\Packets\TimeUpdatePacket;
use HHVMCraft\Core\Networking\Packets\WindowItemsPacket;

class LoginHandler {

  public static function HandleHandshake($packet, $client, $server) {
    $client->username = $packet->username;

    // Sends the string "-" to indicate that no account authentication should take place.
    $client->enqueuePacket(new HandshakeResponsePacket("-"));
  }

  public static function HandleLoginRequest($packet, $client, $server) {

    // Make sure the client has the right protocol version before allowing them to connect.
    if ($packet->protocolVersion == 14) {

      // Respond with details about the world.
      $client->enqueuePacket(new LoginResponsePacket(0, 0, 0));

      // Creating player entity..
      $client->PlayerEntity = $server->EntityManager->addPlayerEntity($client);

      // Handle client inventory.. (WindowItemPacket)
      $client->enqueuePacket(new WindowItemsPacket(0, $client->Inventory->getSlots()));

      // Set the player entity position to the world's spawnpoint
      $client->Entity->Position = $client->World->ChunkProvider->spawnpoint;

      // Send packaet that sets the player's spawnpoint to that location.
      $client->enqueuePacket(new SpawnPositionPacket(
        $client->Entity->Position->x,
        $client->Entity->Position->y,
        $client->Entity->Position->z));

      // send packet that actually sets the player's current position to that position.
      $client->enqueuepacket(new setplayerpositionpacket(
        $client->Entity->Position->x,
        $client->Entity->Position->y,
        $client->Entity->Position->y + $client->Entity->Height,
        $client->Entity->Position->z,
        0,
        0,
        true));

      // Send the world time to the client.
      $client->enqueuePacket(new TimeUpdatePacket(
        $server->World->getTime()));

      // Begin sending chunk data.
      $client->updateChunks();

      // Add player entity to entitymanager, subscribe client to entities.
      $server->EntityManager->addPlayerEntity($client);

      $server->Logger->throwLog("Added new client!");

    }
    else {
      // The client's version is not the same as this server implementation.
      // So, we should disconnect that client with a 'Wrong Version' message.
      $server->Logger->throwWarning("Wrong client version!");
      $server->handleDisconnect($client, true, "Wrong client version!");

    }
  }
}