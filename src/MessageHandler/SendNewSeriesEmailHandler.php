<?php
namespace App\MessageHandler;

use App\Entity\User;
use App\Messages\SeriesWasCreated;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendNewSeriesEmailHandler
{

    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer,
    )
    {}
    
    /** A função __invoke permite que o objeto seja executado como função.
    * Exemplo: 
    * $objeto = new SendNewSeriesEmailHandler();
    * // Executar a função __invoke da classe SendNewSeriesEmailHandler().
    * $objeto(); 
    **/
    public function __invoke(SeriesWasCreated $message)
    {
        $users = $this->userRepository->findAll();
        $usersEmails = array_map(
            fn(User $user) => $user->getEmail(), // Retorno para cada item;
            $users // Array que vai ser iterado.
        );
        $series = $message->series;

        $email = (new TemplatedEmail())

            // ... é um spread operator, converte o array em uma lista de parâmetros.
            ->to(...$usersEmails) 

            ->subject('Nova série criada')
            // Conteúdo sem formatação.
            ->text("Série {$series->getName()} foi criada") 
            // Conteúdo formatado hard-coded.
            // ->html("<h1>Série criada</h1><p>Série \"{$series->getName()}\" foi criada</p>"); 
            // Conteúdo formatado dinâmico.
            ->htmlTemplate("emails/series-created.html.twig")
            // Fornece os parâmetros que serão repassados para o template.
            ->context(compact('series'))
        ; 

        $this->mailer->send($email);
    }
}
