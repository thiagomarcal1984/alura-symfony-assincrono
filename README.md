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

# Entendendo as configurações
De acordo com a documentação (https://symfony.com/doc/current/mailer.html#transport-setup), há 3 tipos de transporte padrão para enviar e-mails:

1. `smtp`: usa um servidor SMTP para mandar o e-mail;
3. `sendmail`: usa o binário local `sendmail` para mandar o e-mail;
3. `native`: usa as configurações do PHP (`php.ini` e `sendmail_path`) para mandar o e-mail;

Usarmos o transporte `smtp` para enviar os e-mails.

Podemos usar dois servidores de SMTP diferentes:
1. https://mailtrap.io/: a plataforma Mailtrap permite a recepção de e-mails destinados ao seu SMTP, sem enviar a mensagem para os destinatários indicados no e-mail. A desvantagem é que o número de e-mails enviados/recebidos é limitado.
2. https://mailcatcher.me/: o Mailcatcher é um programa Ruby que faz o mesmo que o Mailtrap, só que localmente. É possível instalá-lo via Ruby Gems ou via Docker.

Dentro da caixa de entrada do Mailtrap há um combobox chamado `Integrations`. O combobox lista os vários tipos de configuração possíveis para usar a caixa de entrada do Mailtrap (inclusive a do Symfony, do Flask e do Django).

O formato da variável de ambiente `MAILER_DSN` é:
```
{transporte}://{user}:{senha}@{server}:{porta}?{query_parm1=valor1&query_parm2=valor2}
```
Por padrão, o Symfony não envia e-mails até que um processamento assíncrono seja realizado. O profiler do Symfony (ferramenta de debug) permite navegar por todas as requisições feitas na aplicação (sejam elas de quaisquer métodos, inclusive o POST e o DELETE). 

Se você visualizar a requisição de criação da série, verá que o e-mail produzido está enfileirado no painel de e-mails. Para desligar o processamento assíncrono (e enviar o e-mail imediatamente), edite o arquivo `config\packages\messenger.yaml`:

```YAML
# config\packages\messenger.yaml
framework:
    messenger:
        # Resto do código

        routing:
            # Comente ou remova a linha abaixo.
            # Symfony\Component\Mailer\Messenger\SendEmailMessage: async
    # Resto do código
```
## O arquivo .env.local
Geralmente o arquivo `.env` é usado para ambiente de produção, enquanto `.env.local` é usado para ambiente de desenvolvimento. As variáveis de ambiente são declaradas e lidas pelo Symfony da mesma forma em ambos os arquivos.

# Funcionalidades extra
Código PHP de `SeriesController`:
```php
/* ... Resto do código... */
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SeriesController extends AbstractController
{
    /* ... Resto do código... */

    #[Route('/series/create', name: 'app_add_series', methods: ['POST'])]
    public function addSeries(Request $request): Response
    {
        /* ... Resto do código... */
        
        // A classe para o email mudou de Email para TemplatedEmail
        $email = (new TemplatedEmail())
            ->from('sistema@example.com')
            ->to($user->getUserIdentifier())
            ->subject('Nova série criada')
            // Conteúdo sem formatação.
            ->text("Série {$series->getName()} foi criada") 

            // Conteúdo formatado dinâmico.
            ->htmlTemplate("emails/series-created.html.twig")
            // Fornece os parâmetros que serão repassados para o template.
            ->context(compact('series'))
        ; 

        $this->mailer->send($email);
        return new RedirectResponse('/series');
    }
    /* ... Resto do código... */
}
```

Código Twig usado como base para o e-mail:
```HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Série criada</title>
</head>
<body>
    <h1>Série criada</h1>
    <hr>
    <p>Série "{{ series.name }}" foi criada.</p>
</body>
</html>
```
A documentação do Symfony dispõe de ajudas para melhorar a formatação dos e-mails (usando CSS ou outras linguagens que facilitam a criação/organização do conteúdo do e-mail), bem como a inserção de anexos (como imagens): https://symfony.com/doc/current/mailer.html 

# Para saber mais: configurações

Assim como qualquer outro componente Symfony, há diversas configurações que podemos fazer no Mailer. Algumas das mais interessantes são as configurações globais que nos permitem, por exemplo, definir um destinatário e/ou recipiente fixo para todos os e-mails naquele ambiente.

Para definir que todos os e-mails serão enviados com o remetente "sistema@example.com" sem precisar chamar o método from todas as vezes, podemos definir a seguinte configuração no `config\packages\mailer.yaml`:
```YAML
framework:
    mailer:
        envelope:
            sender: 'sistema@example.com'
```
