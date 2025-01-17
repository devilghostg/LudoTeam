<?php

namespace App\Factory;

use App\Entity\Game;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Game>
 */
final class GameFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Game::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $jeuxSociete = ['Monopoly', 'UNO', 'Scrabble', 'Risk', 'Cluedo', 'Trivial Pursuit']; // les jeux de société possible
        $types = ['board', 'card', 'duel']; //les Types de jeux possible sont board, card et duel
        
        return [
            'name' => self::faker()->randomElement($jeuxSociete),
            'maxPlayers' => self::faker()->numberBetween(2, 6),
            'gameType' => self::faker()->randomElement($types),
            'isAvailable' => true,
            'description' => self::faker()->text(200),
            'owner' => UserFactory::random()
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Game $game): void {})
        ;
    }
}
