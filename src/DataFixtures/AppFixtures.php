<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use App\Factory\GameFactory;
use App\Factory\EventFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        UserFactory::createOne([
            'email' => 'admin@test.com',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER']
        ]);

        // Créer 4 utilisateurs normaux
        UserFactory::createMany(4);

        // Créer 10 jeux
        GameFactory::createMany(10, function() {
            return [
                'owner' => UserFactory::random(),
                'isAvailable' => true,
            ];
        });

        // Créer 8 événements
        EventFactory::createMany(8, function() {
            return [
                'organizer' => UserFactory::random(),
                'date' => new \DateTime('+ ' . rand(1, 30) . ' days'),
                'participants' => UserFactory::randomRange(2, 5),
                'games' => GameFactory::randomRange(1, 3),
            ];
        });

        $manager->flush();
    }
}
