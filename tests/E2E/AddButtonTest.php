<?php

namespace App\Tests\E2E;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddButtonTest extends WebTestCase
{
    public function testAddButtonDoesNotExistWhenUserIsNotLoggedIn(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/series');

        $this->assertResponseIsSuccessful();
        // $this->assertSelectorTextContains('h1', 'Hello World');

        // Testa se o botão que aplica as classes a seguir NÃO existe.
        $this->assertSelectorNotExists('.btn.btn-dark.mb-3');
    }

    public function testAddButtonExistsWhenUserIsLoggedIn(): void
    {
        // arrange
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'tma@cdtn.br']);
        
        // act
        $client->loginUser($user);
        $crawler = $client->request('GET', '/series');
        
        // assert
        $this->assertResponseIsSuccessful();
        // Testa se o botão que aplica as classes a seguir existe.
        $this->assertSelectorExists('.btn.btn-dark.mb-3');
    }
}
