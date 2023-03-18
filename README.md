# Código para enviar e-mail
A documentação do Symfony explica mais sobre como funcionar o seu mailer: https://symfony.com/doc/current/mailer.html

Código do `SeriesController`:
```php
/* ... Resto do código... */
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SeriesController extends AbstractController
{
    public function __construct(
        private SeriesRepository $seriesRepository,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
    ) {}

    /* ... Resto do código... */
    
    #[Route('/series/create', name: 'app_add_series', methods: ['POST'])]
    public function addSeries(Request $request): Response
    {
        /* ... Resto do código... */
        $this->seriesRepository->add($series, true);

        $this->addFlash(
            'success',
            "Série \"{$series->getName()}\" adicionada com sucesso"
        );

        $user = $this->getUser();

        $email = (new Email())
            ->from('sistema@example.com')
            ->to($user->getUserIdentifier())
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject('Nova série criada')
            // Conteúdo sem formatação.
            ->text("Série {$series->getName()} foi criada") 
            // Conteúdo formatado.
            ->html('<h1>Série criada</h1><p>Série {$series->getName()} foi criada</p>'); 

        $this->mailer->send($email);
        return new RedirectResponse('/series');
    }
    /* ... Resto do código... */
}
```
Do jeito como está o código, o Symfony vai exibir a seguinte mensagem de erro:
```
EnvNotFoundException  >  InvalidArgumentException
The controller for URI "/series" is not callable: Environment variable not found: "MAILER_DSN".
```
Isso acontece por causa de uma falha na injeção do `MailerInterface`. Para construir o objeto, a variável de ambiente `MAILER_DSN` precisa estar definida.

A próxima aula vai explicar como.
