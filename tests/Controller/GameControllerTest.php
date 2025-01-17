<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GameControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        
        // Test basic
        $crawler = $client->request('GET', '/game');
        self::assertResponseIsSuccessful();
        
        // Test  number 
        $crawler = $client->request('GET', '/game?availability=20');
        self::assertResponseIsSuccessful();
        
        // Test sur l'inclusion d'un nom dans le max players
        $crawler = $client->request('GET', '/game?max_players=test');
        self::assertResponseIsSuccessful();
        
    }
}
