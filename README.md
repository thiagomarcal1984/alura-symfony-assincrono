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

# Processamento assíncrono e mensageria
Explicação do Vinicius Dias sobre mensageria: https://www.youtube.com/watch?v=U5h6B7eSiAE 

A mensageria se baseia em troca de mensagem `assíncrona`: usando envio de e-mails de confirmação como exemplo, o cliente não precisa esperar o processamento do e-mail para obter uma resposta de sucesso. Mensagens de erro, se for o caso, podem ser enviadas `após` o processamento do e-mail.

A estrutura de mensageria é parecida com o padrão de projetos Observer: Um servidor (`publisher`) publica uma mensagem/evento no chamado `Message Broker` ou `Event Bus`. O Message Broker/Event Bus processa o que o publisher pediu e depois manda uma mensagem/evento para os clientes (`subscribers`) ou para o publisher em caso de falha. Assim:

1. O publisher manda uma mensagem/evento para o message broker/event bus (e já manda para os subscribers uma mensagem/evento sobre o processamento no message broker/event bus);
2. O message broker/event bus processa a mensagem/evento do publisher;
3. Após o processamento, o message broker/event bus manda mensagem/evento para os subscribers (ou para o publisher, em caso de falha).

A lógica da mensageria pode usar a metáfora do correio: se quem produz o pacote (o publisher) o entrega para o destinatário (subscriber), ele perde muito tempo sem produzir. A entrega do pacote/encomenda seria tarefa do message broker/event bus.

Estude sobre microsserviços e as ferramentas de mensageria (RabbitMQ, Apache Kafka etc.).

# Configurando transport
Mais detalhes sobre os DSN dos transportes de mensageria na documentação: https://symfony.com/doc/current/messenger.html



No arquivo `config\packages\messenger.yaml` definimos os transports que vão processar certas rotas.
```YAML
# config\packages\messenger.yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            # https://symfony.com/doc/current/messenger.html#transport-configuration
            async: # async é o nome de um dos transportes
                # A variável de ambiente define quem vai armazenar as mensagens da fila.
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                # resto do código do transporte
            # meu_outro_transporte :
                # resto do código do transporte (use o de async como referência)

            # O Doctrine pode receber filas informando quais mensagens não foram 
            # processadas com sucesso. Quando executamos as migrations do Doctrine,
            # a tabela messenger_messages é criada, e é ela que armazena as informações
            # sobre as mensagens.
            failed: 'doctrine://default?queue_name=failed'
            # sync: 'sync://'

        routing:
            # App\Message\MinhaMensagem: meu_outro_transporte
            Symfony\Component\Mailer\Messenger\SendEmailMessage: async
            Symfony\Component\Notifier\Message\ChatMessage: async
            Symfony\Component\Notifier\Message\SmsMessage: async

            # Crie rotas de suas mensagens para os respectivos transportes.
            # No grupo routing, a chave é a mensagem e o valor é o 
            # transporte declarado na seção transports
            # 'App\Message\YourMessage': async
```

Comando do console do Symfony para ler as mensagens armazenadas na tabela `messenger_messages`:
```SQL
php .\bin\console doctrine:query:sql "SELECT * FROM messenger_messages"
```
Até agora só geramos a fila de mensagens. Na próxima aula veremos como processar essa fila.

# Realizando o processamento
A CLI do Symfony dispõe dos seguintes comandos voltados para mensageria:

|Comando                    | Função
|--                         | --
| messenger:consume         | processa as mensagens.
| messenger:failed:remove   | remove as mensagens cujo processamento falhou.
| messenger:failed:retry    | repete o processamento das mensagens cujo processamento falhou.
| messenger:failed:show     | exibe as mensagens cujo processamento falhou.
| messenger:setup-transports| configura um transporte.
| messenger:stop-workers    | pára os workers (os daemons ou serviços de mensageria).

Vamos começar com o comando `messenger:consume` (você pode acrescentar os parâmetros de verbosidade da saída `-v` para normal, `-vv` para verboso ou `-vvv` para debug):
```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:consume


 Which transports/receivers do you want to consume?         
                                                            

Choose which receivers you want to consume messages from in 
order of priority.
Hint: to consume from multiple, use a list of their names, e.g. async, failed

 Select receivers to consume: [async]:
  [0] async
  [1] failed
 > 0



 [OK] Consuming messages from transports "async".           
                                                            

 // The worker will automatically exit once it has received 
 // a stop signal via the messenger:stop-workers command.   

 // Quit the worker with CONTROL-C.

 // Re-run the command with a -vv option to see logs about  
 // consumed messages.

```

Exemplo de comando mais verboso (a primeira série entrou na fila antes da execução do comando):
```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:consume -vv


 Which transports/receivers do you want to consume?


Choose which receivers you want to consume messages from in order of priority.
Hint: to consume from multiple, use a list of their names, e.g. async, failed

 Select receivers to consume: [async]:
  [0] async
  [1] failed
 > 0



 [OK] Consuming messages from transports "async".


 // The worker will automatically exit once it has received a stop signal via the messenger:stop-workers command.

 // Quit the worker with CONTROL-C.

18:44:09 INFO      [messenger] Received message Symfony\Component\Mailer\Messenger\SendEmailMessage ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]
18:44:12 INFO      [messenger] Message Symfony\Component\Mailer\Messenger\SendEmailMessage handled by Symfony\Component\Mailer\Messenger\MessageHandler::__invoke ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage","handler" => "Symfony\Component\Mailer\Messenger\MessageHandler::__invoke"]
18:44:12 INFO      [messenger] Symfony\Component\Mailer\Messenger\SendEmailMessage was handled successfully (acknowledging to transport). ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]

18:44:40 INFO      [messenger] Received message Symfony\Component\Mailer\Messenger\SendEmailMessage ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]
18:44:41 INFO      [messenger] Message Symfony\Component\Mailer\Messenger\SendEmailMessage handled by Symfony\Component\Mailer\Messenger\MessageHandler::__invoke ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage","handler" => "Symfony\Component\Mailer\Messenger\MessageHandler::__invoke"]
18:44:41 INFO      [messenger] Symfony\Component\Mailer\Messenger\SendEmailMessage was handled successfully (acknowledging to transport). ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]
```
Por último, o comando executado no modo debug:
```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:consume -vvv


 Which transports/receivers do you want to consume?


Choose which receivers you want to consume messages from in order of priority.
Hint: to consume from multiple, use a list of their names, e.g. async, failed

 Select receivers to consume: [async]:
  [0] async
  [1] failed
 > 0



 [OK] Consuming messages from transports "async".


 // The worker will automatically exit once it has received a stop signal via the messenger:stop-workers command.

 // Quit the worker with CONTROL-C.

18:48:57 INFO      [messenger] Received message Symfony\Component\Mailer\Messenger\SendEmailMessage
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"
]
18:48:57 DEBUG     [php] User Warning: Configure the "curl.cainfo", "openssl.cafile" or "openssl.capath" php.ini setting to enable the CurlHttpClient
[
  "exception" => Symfony\Component\ErrorHandler\Exception\SilencedErrorContext^ {
    +count: 1
    -severity: E_USER_WARNING
    trace: {
      D:\alura\symfony-assincrono\vendor\symfony\http-client\HttpClient.php:57 { …}
      D:\alura\symfony-assincrono\var\cache\dev\Container1yzM6v5\App_KernelDevDebugContainer.php:1065 {
        › {
        ›     $a = \Symfony\Component\HttpClient\HttpClient::create([], 6);
        › 
      }
    }
  }
]
18:48:57 DEBUG     [php] User Notice: Upgrade the curl extension or run "composer require amphp/http-client" to perform async HTTP operations, including full HTTP/2 support
[
  "exception" => Symfony\Component\ErrorHandler\Exception\SilencedErrorContext^ {
    +count: 1
    -severity: E_USER_NOTICE
    trace: {
      D:\alura\symfony-assincrono\vendor\symfony\http-client\HttpClient.php:64 { …}
      D:\alura\symfony-assincrono\var\cache\dev\Container1yzM6v5\App_KernelDevDebugContainer.php:1065 {
        › {
        ›     $a = \Symfony\Component\HttpClient\HttpClient::create([], 6);
        › 
      }
    }
  }
]
18:48:57 DEBUG     [mailer] Email transport "Symfony\Component\Mailer\Transport\Smtp\SmtpTransport" starting
18:48:59 DEBUG     [mailer] Email transport "Symfony\Component\Mailer\Transport\Smtp\SmtpTransport" started
18:49:00 INFO      [messenger] Message Symfony\Component\Mailer\Messenger\SendEmailMessage handled by Symfony\Component\Mailer\Messenger\MessageHandler::__invoke
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage",
  "handler" => "Symfony\Component\Mailer\Messenger\MessageHandler::__invoke"
]
18:49:00 INFO      [messenger] Symfony\Component\Mailer\Messenger\SendEmailMessage was handled successfully (acknowledging to transport).
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"
]
18:49:04 INFO      [messenger] Received message Symfony\Component\Mailer\Messenger\SendEmailMessage
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"
]
18:49:05 INFO      [messenger] Message Symfony\Component\Mailer\Messenger\SendEmailMessage handled by Symfony\Component\Mailer\Messenger\MessageHandler::__invoke
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage",
  "handler" => "Symfony\Component\Mailer\Messenger\MessageHandler::__invoke"
]
18:49:05 INFO      [messenger] Symfony\Component\Mailer\Messenger\SendEmailMessage was handled successfully (acknowledging to transport).
[
  "class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"
]
```

Geralmente a fila de falha é persistida em um banco de dados em disco mesmo; as demais filas costumam ser colocadas em memória para melhorar a performance.

# Criando uma mensagem
Como criar um manipulador de mensagens do Symfony: https://symfony.com/doc/current/messenger.html#creating-a-message-handler

O padrão para nomear classes de mensagens é mencionar que o evento ocorreu no passado. Por exemplo: quando a série é criada, nomeamos a classe como `SeriesWasCreated`.

A classe de mensagem nada mais é do que uma classe PHP simples. Da forma como está, a mensagem pode até entrar na fila de mensagens, mas ela não será processada por que não há indicação de quem pode processa-la:

```YAML
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                # resto do código do transporte.
        routing:
            # A rota abaixo permite que SeriesWasCreated entre na fila.
            # Mas ela não vai ser processada, porque falta quem processe a mensagem.
            App\Messages\SeriesWasCreated: async
```
Código da mensagem `SeriesWasCreated.php`:
```php
<?php
namespace App\Messages;

use App\Entity\Series;

class SeriesWasCreated
{
    public function __construct(
        public readonly Series $series
    ) {}
}
```
# Definindo um handler
Manipuladores de mensagem geralmente são sufixados com `Handler`. Eles são anotados com `AsMessageHandler` e também definem a função `__invoke`, que permite executar o objeto como função.

No exemplo, a lógica de envio de e-mail foi movida de `SeriesController` para o handler `SendNewSeriesEmailHandler`, conforme código a seguir:

```php
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
    *   Exemplo: 
    *       $objeto = new SendNewSeriesEmailHandler();
    *       // Executar a função __invoke() da classe SendNewSeriesEmailHandler.
    *       $objeto(); 
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
            
            // Conteúdo formatado dinâmico.
            ->htmlTemplate("emails/series-created.html.twig")
            
            // Fornece os parâmetros que serão repassados para o template.
            ->context(compact('series'))
        ; 

        $this->mailer->send($email);
    }
}
```
> PS.: Para sistemas que não estejam usando o PHP 8.1, o Symfony permite que, ao invés de usar o atributo `AsMessageHandler`, a classe possa implementar a interface `Symfony\Component\Messenger\Handler\MessageHandlerInterface`.


Aqui definimos o handler, na aula passada definimos a mensagem. Na próxima aula veremos como enviar a mensagem para ser processada.

# Enviando mensagem
O envio da mensagem é feito dentro de `SeriesController` por meio de um objeto que implementa a interface `MessageBusInterface`.

Código de `SeriesController`:
```php
/* ... Resto do código... */
use App\Messages\SeriesWasCreated;
use Symfony\Component\Messenger\MessageBusInterface;

class SeriesController extends AbstractController
{
    public function __construct(
        private SeriesRepository $seriesRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messenger,
    ) {}
    /* ... Resto do código... */
    
    #[Route('/series/create', name: 'app_add_series', methods: ['POST'])]
    public function addSeries(Request $request): Response
    {
        /* ... Resto do código... */
        // O messenger procura os handlers para as mensagens enviadas
        // como parâmetro para o método dispatch($mensagem).
        $this->messenger->dispatch(new SeriesWasCreated($series));
        /* ... Resto do código... */
    }
}
```

Saída do `messenger:consume` após a alteração no código de `SeriesController`:
```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:consume -vv


 Which transports/receivers do you want to consume?         
                                                            

Choose which receivers you want to consume messages from in 
order of priority.
Hint: to consume from multiple, use a list of their names, e.g. async, failed

 Select receivers to consume: [async]:
  [0] async
  [1] failed
 >


                                                            
 [OK] Consuming messages from transports "async".           
                                                            

 // The worker will automatically exit once it has received 
 // a stop signal via the messenger:stop-workers command.   

 // Quit the worker with CONTROL-C.

20:04:54 INFO      [messenger] Received message App\Messages\SeriesWasCreated ["class" => "App\Messages\SeriesWasCreated"]
20:04:54 INFO      [messenger] Sending message Symfony\Component\Mailer\Messenger\SendEmailMessage with async sender using Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage","alias" => "async","sender" => "Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport"]
20:04:54 INFO      [messenger] Message App\Messages\SeriesWasCreated handled by App\MessageHandler\SendNewSeriesEmailHandler::__invoke ["class" => "App\Messages\SeriesWasCreated","handler" => "App\MessageHandler\SendNewSeriesEmailHandler::__invoke"]
20:04:54 INFO      [messenger] App\Messages\SeriesWasCreated was handled successfully (acknowledging to transport). ["class" => "App\Messages\SeriesWasCreated"]
20:04:54 INFO      [messenger] Received message Symfony\Component\Mailer\Messenger\SendEmailMessage ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]
20:04:57 INFO      [messenger] Message Symfony\Component\Mailer\Messenger\SendEmailMessage handled by Symfony\Component\Mailer\Messenger\MessageHandler::__invoke ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage","handler" => "Symfony\Component\Mailer\Messenger\MessageHandler::__invoke"]
20:04:57 INFO      [messenger] Symfony\Component\Mailer\Messenger\SendEmailMessage was handled successfully (acknowledging to transport). ["class" => "Symfony\Component\Mailer\Messenger\SendEmailMessage"]
```
Certifique-se que o daemon/worker que está consumindo as mensagens corresponde ao mesmo código que está rodando no servidor web, senão o servidor web dispara mensagens que o daemon desconhece.

# Para saber mais: produção
Como garantir que o daemon de consumo da fila de mensagens sempre vai ficar de pé? Veja na documentação:

* Messenger: Sync & Queued Message Handling - Deploying to Production: https://symfony.com/doc/current/messenger#deploying-to-production
* Supervisor: https://symfony.com/doc/current/messenger#messenger-supervisor
* Systemd no Linux: https://symfony.com/doc/current/messenger#messenger-systemd

# Analisando falhas e fazendo log

Exibindo com a CLI as mensagens cujo processamento falharam:

```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:failed:show

There is 1 message pending in the failure transport.
 ---- ------------------------------- --------------------- ---------------------------------------------------------
  Id   Class                           Failed at             Error
       
 ---- ------------------------------- --------------------- ---------------------------------------------------------
  13   App\Messages\SeriesWasCreated   2023-03-19 15:17:57   No handler for message "App\Messages\SeriesWasCreated".
 ---- ------------------------------- --------------------- ---------------------------------------------------------

 // Run messenger:failed:show {id} --transport=failed -vv to see message details.
 ```

Se você informar o ID da mensagem para o comando `messenger:failed:show`, você obterá informações mais detalhadas da mensagem cujo ID foi informado:
```
PS D:\alura\symfony-assincrono> php .\bin\console messenger:failed:show 13
There is 1 message pending in the failure transport.

Failed Message Details
======================

 ------------- -------------------------------------------------------------------- 
  Class         App\Messages\SeriesWasCreated
  Message Id    13
  Failed at     2023-03-19 15:17:57
  Error         No handler for message "App\Messages\SeriesWasCreated".
  Error Code    0
  Error Class   Symfony\Component\Messenger\Exception\NoHandlerForMessageException
  Transport     async
 ------------- --------------------------------------------------------------------

 Message history:
  * Message failed at 2023-03-19 15:17:50 and was redelivered
  * Message failed at 2023-03-19 15:17:51 and was redelivered
  * Message failed at 2023-03-19 15:17:53 and was redelivered
  * Message failed at 2023-03-19 15:17:57 and was redelivered

 Re-run command with -vv to see more message & error details.

 Run messenger:failed:retry 13 --transport=failed to retry this message.
 Run messenger:failed:remove 13 --transport=failed to delete it.
 ```

 Ao executar o comando `messenger:failed:retry <id>`, a mensagem não é imediatamente reprocessada. Ela é reenviada para fila do message broker/event bus:
 ```
 PS D:\alura\symfony-assincrono> php .\bin\console messenger:failed:retry 13 

 // Quit this command with CONTROL-C.

 // Re-run the command with a -vv option to see logs about consumed messages.

There is 1 message pending in the failure transport.
To retry all the messages, run messenger:consume failed

Failed Message Details
======================

 ------------- -------------------------------------------------------------------- 
  Class         App\Messages\SeriesWasCreated
  Message Id    13
  Failed at     2023-03-19 15:17:57
  Error         No handler for message "App\Messages\SeriesWasCreated".
  Error Code    0
  Error Class   Symfony\Component\Messenger\Exception\NoHandlerForMessageException
  Transport     async
 ------------- --------------------------------------------------------------------

 Message history:
  * Message failed at 2023-03-19 15:17:50 and was redelivered
  * Message failed at 2023-03-19 15:17:51 and was redelivered
  * Message failed at 2023-03-19 15:17:57 and was redelivered

 Re-run command with -vv to see more message & error details.

 Do you want to retry (yes) or delete this message (no)? (yes/no) [yes]:
 >

 [OK] All done!                                                                                                
```

 Podemos indicar opcionalmente de qual transporte buscaremos a mensagem que vamos repetir o processamento, com o parâmetro `--transport=nome_do_transporte`.

 Código do novo MessageHandler para registrar no log do Symfony (`var/log/<ambiente>.log`) a inserção de uma nova série:

 ```php
 <?php
namespace App\MessageHandler;

use App\Messages\SeriesWasCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LogNewSeriesHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(SeriesWasCreated $message)
    {
        $this->logger->info("A new series was created", [ 
            "series" => [
                "seriesName" => $message->series->getName()
            ] 
        ]);
    }
}
 ```

Saída em `/var/log/<ambiente>.log`:

```
[2023-03-19T15:57:43.219726+00:00] doctrine.DEBUG: Beginning transaction [] []
[2023-03-19T15:57:43.220348+00:00] doctrine.DEBUG: Executing statement: SELECT m.* FROM messenger_messages m WHERE (m.delivered_at is null OR m.delivered_at < ?) AND (m.available_at <= ?) AND (m.queue_name = ?) ORDER BY available_at ASC LIMIT 1  (parameters: array{"1":"2023-03-19 14:57:43","2":"2023-03-19 15:57:43","3":"default"}, types: array{"1":2,"2":2,"3":2}) {"sql":"SELECT m.* FROM messenger_messages m WHERE (m.delivered_at is null OR m.delivered_at < ?) AND (m.available_at <= ?) AND (m.queue_name = ?) ORDER BY available_at ASC LIMIT 1 ","params":{"1":"2023-03-19 14:57:43","2":"2023-03-19 15:57:43","3":"default"},"types":{"1":2,"2":2,"3":2}} []
[2023-03-19T15:57:43.221216+00:00] doctrine.DEBUG: Executing statement: UPDATE messenger_messages SET delivered_at = ? WHERE id = ? (parameters: array{"1":"2023-03-19 15:57:43","2":19}, types: array{"1":2,"2":2}) {"sql":"UPDATE messenger_messages SET delivered_at = ? WHERE id = ?","params":{"1":"2023-03-19 15:57:43","2":19},"types":{"1":2,"2":2}} []
[2023-03-19T15:57:43.235236+00:00] doctrine.DEBUG: Committing transaction [] []
[2023-03-19T15:57:43.266400+00:00] messenger.INFO: Received message App\Messages\SeriesWasCreated {"class":"App\\Messages\\SeriesWasCreated"} []
[2023-03-19T15:57:43.272633+00:00] doctrine.INFO: Connecting with parameters array{"url":"<redacted>","driver":"pdo_sqlite","host":"localhost","port":null,"user":"root","password":null,"driverOptions":[],"defaultTableOptions":[],"path":"D:\\alura\\symfony-assincrono/var/data.db","charset":"utf8"} {"params":{"url":"<redacted>","driver":"pdo_sqlite","host":"localhost","port":null,"user":"root","password":null,"driverOptions":[],"defaultTableOptions":[],"path":"D:\\alura\\symfony-assincrono/var/data.db","charset":"utf8"}} []
[2023-03-19T15:57:43.274520+00:00] app.INFO: A new series was created {"series":{"seriesName":"The Office"}} []
[2023-03-19T15:57:43.275874+00:00] messenger.INFO: Message App\Messages\SeriesWasCreated handled by App\MessageHandler\LogNewSeriesHandler::__invoke {"class":"App\\Messages\\SeriesWasCreated","handler":"App\\MessageHandler\\LogNewSeriesHandler::__invoke"} []
```
# Corrigindo Form e DTO
Dois passos:
1. Refatorar O DTO, renomeando ele de `SeriesCreateFromInput` para `SeriesCreationInputDTO`, e atualizando as referências ao DTO;
2. Mover as regras de validação da entidade `Series` para o DTO `SeriesCreationInputDTO`, já que o DTO é que será responsável pela validação, não mais a entidade.

Abaixo o código de `SeriesCreationInputDTO`, com as constraints retiradas da entidade `Series`:
```php
<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SeriesCreationInputDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 5)]
        public string $seriesName = '',
        
        #[Assert\Positive] // Deve ser maior que zero.
        public int $seasonsQuantity = 0,
        
        #[Assert\Positive] // Deve ser maior que zero.
        public int $episodesPerSeason = 0,
    ) {
    }
}
```

# Solicitando uma imagem
Uma boa prática é fazer com que os setters retornem o próprio objeto! Isso possibilita o encadeamento de atribuições:
```php
    $pessoa
        ->setName('Thiago')
        ->setAge(38)
        ->setAtivo(true)
    ;
```
Novo código para a entidade `Series`:
```php
/* ... Resto do código... */
class Series
{
    /* ... Resto do código... */
    public function __construct(
        #[ORM\Column]
        private string $name, // O nome não pode mais ser opcional.
        #[ORM\Column]
        private ?string $coverImagePath = null, // O path da imagem pode ser opcional.
    ) {
        $this->seasons = new ArrayCollection();
    }

    /* ... Resto do código... */
    public function getCoverImagePath(): ?string
    {
        return $this->coverImagePath;
    }

    public function setCoverImagePath(string $coverImagePath): self
    {
        $this->coverImagePath = $coverImagePath;

        return $this;
    }
}
```
Depois de alterar a entidade, precisamos criar a migration:
```
php bin\console make:migration
```
Arquivos gerados para DB Sqlite costumam ser mais verbosos. Edite o arquivo conforme necessário. O arquivo da migration agora ficou assim:
```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230319163230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inclusão do campo da capa da imagem (cover_image_path).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series ADD COLUMN cover_image_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE series DROP COLUMN cover_image_path');
    }
}
```
Aplicação da migração:
```
php .\bin\console doctrine:migrations:migrate
```

Alteração em `SeriesCreationInputDTO`:
```php
<?php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SeriesCreationInputDTO
{
    public function __construct(
        /* ... Resto do código... */

        #[Assert\File] 
        public string $coverImage = '',
    ) {
    }
}
```
Mudanças no formulário `SeriesType`:
```php
/* ... Resto do código... */
use Symfony\Component\Form\Extension\Core\Type\FileType;

class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* ... Resto do código... */
            ->add('coverImage', FileType::class, ['label' => 'Imagem de capa'])
            ->add('save', SubmitType::class, ['label' => $options['is_edit'] ? 'Editar' : 'Adicionar'])
            ->setMethod($options['is_edit'] ? 'PATCH' : 'POST')
        ;
    }

    /* ... Resto do código... */
}
```

Finalmente, o acréscimo da linha correspondente ao campo coverImage no template Twig:
```HTML
{% extends 'base.html.twig' %}

{% block title %}{{ series is defined ? 'Editar' : 'Nova' }} Série{% endblock %}

{% block body %}
    {{ form_start(seriesForm) }}
    {{ form_row(seriesForm.seriesName) }}
    {{ form_row(seriesForm.seasonsQuantity) }}
    {{ form_row(seriesForm.episodesPerSeason) }}
    
    {{ form_row(seriesForm.coverImage) }}
    
    {{ form_widget(seriesForm.save, {'attr': {'class': 'btn-dark'}}) }}
    {{ form_end(seriesForm) }}
{% endblock %}
```

# Para saber mais: sem framework
Antes de realizar qualquer tarefa com um framework é MUITO importante que a gente saiba realizá-la sem ele, para garantir que nós conhecemos a base e sabemos com o que estamos trabalhando.

No seguinte vídeo da Alura+, Vinícius Dias mostra como lidar com upload de arquivos com PHP:

Upload de arquivos com PHP (https://cursos.alura.com.br/extra/alura-mais/upload-de-arquivos-com-php-c205)

Inclusive, falando sobre estudar bem a base antes de estudar um framework, vou deixar uma leitura que pode ser interessante aqui:

Princípios ou Ferramentas - O que estudar (https://dias.dev/2020-04-23-principios-ou-ferramentas-o-que-estudar/)

# Manipulando upload
Duas abordagens para guardar arquivos na entidade ou no DTO: 
1. usando strings; ou
2. usando um tipo próprio do framework (as classes `File` ou `UploadedFile` do namespace `Symfony\Component\HttpFoundation\File\`)

A segunda abordagem não é recomendada porque cria uma dependência entre o código do domínio e o framework. Quanto mais livre o código for do framework, melhor para a sua compatibilidade.

Veja o código `SeriesCreationInputDTO`:
```php
<?php
namespace App\DTO;

// use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class SeriesCreationInputDTO
{
    public function __construct(
        /* ... Resto do código... */
        
        #[Assert\File] 
        // public ?File $coverImage = null,
        public ?string $coverImage = null,
    ) {
    }
}
```

A abordagem para mover o arquivo para um outro local é:
1. obter o caminho temporário do arquivo (isso pode ser feito usando a variável `$_FILES`, ou mesmo as classes `File` ou `UploadedFile`; e 
2. usando a função `move_uploaded_file($origem, $destino)` para mover efetivamente o arquivo.

A classe `Symfony\Component\HttpFoundation\File\{File, UploadedFile}` contém o método `move`, que move o arquivo com o nome e diretório indicados nele.

## Modificações em `SeriesController`
```php
/* ... Resto do código... */
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class SeriesController extends AbstractController
{
    public function __construct(
        private SeriesRepository $seriesRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messenger,
        // Converte texto para caracteres mais seguros para URLs.
        private SluggerInterface $slugger, 
    ) {}

    /* ... Resto do código... */
    #[Route('/series/create', name: 'app_add_series', methods: ['POST'])]
    public function addSeries(Request $request): Response
    {
        $input = new SeriesCreationInputDTO();
        $seriesForm = $this->createForm(SeriesType::class, $input)
            ->handleRequest($request);

        /** @var UploadedFile $uploadedCoverImage */
        $uploadedCoverImage = $seriesForm->get('coverImage')->getData();
        if ($uploadedCoverImage) {
            $originalFilename = pathinfo( // Função nativa do PHP.
                // Busca nome original...
                $uploadedCoverImage->getClientOriginalName(), 
                // ... e retorna só o nome do arquivo sem extensão.
                PATHINFO_FILENAME
            );
            
            // Usa o slugger para usar caracteres seguros no nome do arquivo.
            $safeFilename = $this->slugger->slug($originalFilename);
            
            // Define um nome único de arquivo que evite sobre-escrita.
            $newFilename = $safeFilename . 
                // uniqid é uma função do PHP para gerar IDs únicas.
                '-' . uniqid() . 
                // Lê o conteúdo do arquivo para adivinhar a extensão (mais seguro).
                '.' . $uploadedCoverImage->guessExtension(); 
                
            // Resultado: arquivo-641775c4977c7.jpg
            $input->coverImage = $newFilename;
        }

        $uploadedCoverImage->move(
            // Diretório de destino. Parâmetro obtido do
            // arquivo config/services.yaml
            $this->getParameter('cover_image_directory'), 
            // Nome do arquivo de destino. 
            // Se omitido, o nome original é passado como 2o parm.
            $newFilename, 
        );

        /* ... Resto do código... */
        $series->setCoverImagePath($newFilename);
        $this->seriesRepository->add($series, true);
        /* ... Resto do código... */
    }
    /* ... Resto do código... */
}
```

## Comentários sobre a opção mapped nos campos dos formulários do Symfony
A opção `mapped` marcada como false signficica que o campo não está associado a qualquer propriedade da entidade. No exemplo, vamos desassociar o campo `coverImage` da `data_class` configurada para o formluário, ou seja, `SeriesCreationInputDTO`.

Código de `SeriesType`:
```php
/* ... Resto do código... */
class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* ... Resto do código... */
            
            // A opção 'mapped'=>false significa que o campo coverImage da entidade não será
            // preenchido (coverImage sempre vai ser nulo). Não é o DTO (data_class) que vai 
            // controlar o campo, mas sim o controller.
            
            ->add('coverImage', FileType::class, ['label' => 'Imagem de capa', 'mapped' => false])
            /* ... Resto do código... */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Classe para a qual ocorre o mapeamento dos campos para preenchimento.
            'data_class' => SeriesCreationInputDTO::class, 
            'is_edit' => false,
        ]);

        /* ... Resto do código... */
    }
}
```
## O arquivo services.yaml
É possível obter parâmetros usados reiteradas vezes na aplicaçã que independem da máquina onde a aplicação está instalada. Basta usar o arquivo `services.yaml`.

```YAML
parameters:
    diretorio1 : '/diretorio1'
    diretorio2 : '/diretorio2'
    diretorio3 : '/diretorio3'
```
Para ler os parâmetros de `services.yaml`, podemos usar:
1. A partir dos controllers, usamos o método `$this->getParameter('nome_do_parametro')`;
2. Para invocar os parâmetros de fora dos controllers, use método `$this->get('nome_do_parametro')` da classe injetável `Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface`.

## O caminho padrão do projeto 
O path `%kernel.project_dir%` é a curinga para a raiz do projeto. Repare que esse caminho foi usado no arquivo de configuração `services.yaml` para definir o diretório para onde vão as capas das séries:

```YAML
# config/services.yaml
parameters:
    cover_image_directory: '%kernel.project_dir%/public/uploads/covers'
```

# Exibindo a capa

A validação do tipo de arquivo poderia ser feita na classe `SeriesCreationInputDTO`:
```php
/* ... Resto do código... */
use Symfony\Component\Validator\Constraints as Assert;

class SeriesCreationInputDTO
{
    public function __construct(
        /* ... Resto do código... */
        #[Assert\File(mimeTypes: 'image/*')] 
        // public ?File $coverImage = null,
        public ?string $coverImage = null,
    ) {
    }
}
```
A validação de Assert\File funcionaria **se** o formulário `SeriesType` permitisse o mapeamento. Neste caso, somente a validação em `SeriesType` funcionaria:

```php
/* ... Resto do código... */
use Symfony\Component\Validator\Constraints\File;

class SeriesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /* ... Resto do código... */

            // A opção 'mapped'=>false significa que o campo coverImage da entidade não será
            // preenchido (coverImage sempre vai ser nulo). Não é o DTO (data_class) que vai 
            // controlar o campo, mas sim o controller.
            ->add(
                'coverImage', 
                FileType::class, 
                [
                    'label' => 'Imagem de capa',
                    'mapped' => false,
                    'required' => false,

                    // As constraints aplicáveis ao DTO/Entidades entrariam aqui.
                    'constraints' => [ 
                        new File(mimeTypes: 'image/*'),
                    ],
                ]
            )
            ->add('save', SubmitType::class, ['label' => $options['is_edit'] ? 'Editar' : 'Adicionar'])
            ->setMethod($options['is_edit'] ? 'PATCH' : 'POST')
        ;
    }
    /* ... Resto do código... */
}
```
Exibição da imagem no template Twig:
```HTML
{# Resto do código #}
{% block body %}
<div class="text-center">
    <img 
        src="{{ asset('uploads/covers/') ~ series.coverImagePath }}" 
        alt="Imagem de capa da série {{ series.name }}" 
        class="image-fluid mb-3" 
    />
</div>
{# Resto do código #}
{% endblock %}
```
Repare que no Twig o operador de concatenação é o til (`~`).

A função `asset` toma como referência a pasta public do projeto.

# Excluindo o arquivo
Primeiro: A criação da mensagem para a exclusão da imagem de capa após a exclusão no banco de dados consiste numa classe simples que recebe uma `Series` como parâmetro do construtor:

```php
<?php
namespace App\Messages;

use App\Entity\Series;

class SeriesWasDeleted
{
    public function __construct(
        public readonly Series $series
    ) {}
}
```

Segundo: O manipulador da mensagem deve ter a anotação `AsMessageHandler` e a função `invoke` definida com o parâmetro correspondente à mensagem que se deseja processar (no caso, a `SeriesWasDeleted`):
```php
<?php
namespace App\MessageHandler;

use App\Messages\SeriesWasDeleted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteSeriesImageHandler
{

    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {}
    
    /** A função __invoke permite que o objeto seja executado como função.
    * Exemplo: 
    * $objeto = new SendNewSeriesEmailHandler();
    * // Executar a função __invoke da classe SendNewSeriesEmailHandler().
    * $objeto(); 
    **/
    public function __invoke(SeriesWasDeleted $message)
    {
        $coverImagePath = $message->series->getCoverImagePath();
        
        $path = ($this->parameterBag->get('cover_image_directory') 
            . DIRECTORY_SEPARATOR
            . $coverImagePath
        );

        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        // unlink é o comando do PHP para apagar um arquivo.
        unlink($path);
    }
}
```
Terceiro: editamos o arquivo `config/packages/messenger.yaml` para definir qual transporte vai consumir as mensagens disparadas:
```yaml
framework:
    messenger:
        # Resto do código...

        transports:
            async:
                # Resto do código...

            # Resto do código...
        routing:
            App\Messages\SeriesWasDeleted: async
            # Resto do código...
```

Quarto: Editamos `SeriesController` para que as mensagens sejam disparadas ao excluirmos uma série do banco de dados:
```php
/* ... Resto do código... */

class SeriesController extends AbstractController
{
    /* ... Resto do código... */
    #[Route(
        '/series/delete/{series}',
        name: 'app_delete_series',
        methods: ['DELETE'],
    )]
    public function deleteSeries(Series $series, Request $request): Response
    {
        $this->seriesRepository->remove($series, true);

        // O messenger procura os handlers para as mensagens enviadas
        // como parâmetro para o método dispatch($mensagem).
        $this->messenger->dispatch(new SeriesWasDeleted($series));

        $this->addFlash('success', 'Série removida com sucesso');

        return new RedirectResponse('/series');
    }


    /* ... Resto do código... */
}
```

Quinto: vamos mudar no template `index.html.twig` do controlador `series`: ao invés de fornecermos o id (que era o primeiro parâmetro da ação `deleteSeries`, do tipo inteiro), fornecermos a série (que é agora o primeiro parâmetro do tipo `Series` na ação `deleteSeries`):
```HTML
<form method="post" action="{{ path('app_delete_series', {series: series.id}) }}">
    <input type="hidden" name="_method" value="DELETE">

    <button class="btn btn-sm btn-danger">
        X
    </button>
</form>
```
Perceba que na função `path()` podemos fornecer para o parâmetro `series` tanto o valor inteiro `series.id` quanto o objeto `series` inteiro. A ação `deleteSeries` vai conseguir processar de acordo.

# Tipos de testes
O arquivo `tests\bootstrap.php` inicializa o ambiente de testes. Já o arquivo `phpunit.xml.dist` (na raiz do projeto) contém as configurações do PHPUnit, inclusive o diretório a partir do qual serão procurados os testes automatizados para execução.

Comando para rodar testes com o Symfony/PHPUnit:
```
php .\bin\phpunit
```

Se você estivesse usando a versão mínima do Symfony, você precisaria instalar o pacote de testes no Symfony, por meio do comando:
```
composer require --dev symfony/test-pack
```
Classificação dos testes:
1. Testes unitários (Unit Tests): testa unidades individuais de código (classes ou arquivos, por exemplo);
2. Testes de integração (Integrations Tests): testam a combinação de algumas unidades de código.
3. Testes de aplicação (Application Tests): testam o comportamento completo da aplicação. Neles se fazem requisições HTTP (reais ou simuladas) e verificam se a resposta é a esperada.

## Desenvolvendo testes de integração
Os testes de integração extendem da classe `KernelTestCase`, que permitem acesso a variáveis de ambiente e inicalização do kernel do Symfony.

Existe um bundle que isola as transações associadas aos testes, de maneira que o banco de dados não seja modificado durante os testes: o `DAMADoctrineTestBundle`, que pode ser instalado por meio do comando:

```
composer require --dev dama/doctrine-test-bundle
```
Também podemos usar um bundle que gera `data fixtures`, dados iniciais que serão usados apenas no ambiente de testes.
```
composer require --dev doctrine/doctrine-fixtures-bundle
```
## Desenvolvendo testes de aplicação
Os testes de aplicação extendem da classe `WebTestCase`, que cria um cliente HTTP que gera requisições e retornam as respostas que serão testadas.

# Data fixtures
Instale o `DAMADoctrineTestBundle`. O arquivo de configuração `config\packages\test\dama_doctrine_test_bundle.yaml` será criado após a instalação:

```
composer require --dev dama/doctrine-test-bundle

Info from https://repo.packagist.org: #StandWithUkraine
Using version ^7.2 for dama/doctrine-test-bundle
./composer.json has been updated
Running composer update dama/doctrine-test-bundle
Loading composer repositories with package information
Updating dependencies
Lock file operations: 1 install, 0 updates, 0 removals
  - Locking dama/doctrine-test-bundle (v7.2.1)
Writing lock file
Installing dependencies from lock file (including require-dev)
Package operations: 1 install, 0 updates, 0 removals
  - Downloading dama/doctrine-test-bundle (v7.2.1)
  - Installing dama/doctrine-test-bundle (v7.2.1): Extracting archive
Generating optimized autoload files
109 packages you are using are looking for funding.
Use the `composer fund` command to find out more!

Symfony operations: 1 recipe (0f3e9d6615e376cf84953a826de53647)
  -  WARNING  dama/doctrine-test-bundle (>=4.0): From github.com/symfony/recipes-contrib:main
    The recipe for this package comes from the "contrib" repository, which is open to community contributions. 
    Review the recipe at https://github.com/symfony/recipes-contrib/tree/main/dama/doctrine-test-bundle/4.0    

    Do you want to execute this recipe?
    [y] Yes
    [n] No
    [a] Yes for all packages, only for the current installation session
    [p] Yes permanently, never ask again for this project
    (defaults to n): y
  - Configuring dama/doctrine-test-bundle (>=4.0): From github.com/symfony/recipes-contrib:main
Executing script cache:clear [OK]
Executing script assets:install public [OK]

 What's next? 


Some files have been created and/or updated to configure your new packages.
Please review, edit and commit them: these files are yours.
```

Modifique o arquivo `phpunit.xml.dist`:
```XML
<phpunit>
    <!-- Resto do código -->

    <extensions>
        <extension class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
    </extensions>
   
</phpunit>
```

A instalação de data fixtures do Doctrine pode ser feita com ou sem o Symfony Flex. 

Com o Symfony Flex:

```
composer require --dev orm-fixtures
```
Sem o Symfony Flex:
```
composer require --dev doctrine/doctrine-fixtures-bundle
```

Terminada a instalação, o arquivo `src/DataFixtures/Appfixtures.php` será criado. Depois de instalar o bundle de data fixtures do Doctrine, crie a fixture de usuário com o comando:

```
php .\bin\console make:fixture


 The class name of the fixtures to create (e.g. AppFixtures):
 > UserFixtures

 created: src/DataFixtures/UserFixtures.php

 
  Success! 
 

 Next: Open your new fixtures class and start customizing it.
 Load your fixtures by running: php .\bin\console doctrine:fixtures:load
 Docs: https://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
 ```

Criada a fixture, complete o seu código com a lógica de criação do objeto falso (data fixture):
```PHP
<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('tma@cdtn.br');
        
        $password = $this->hasher->hashPassword($user, 'thiago');
        $user->setPassword($password);
        
        $manager->persist($user);
        $manager->flush();
    }
}
```
## Configurações do banco de dados de teste

Altere as variáveis de **ambiente de teste** no arquivo `.env.test`
```conf
# Resto do código
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
# Impedir o envio de e-mails no ambiente de testes:
MAILER_DSN=null://null 
```

Crie o banco de dados **no ambiente de teste**:
```
php .\bin\console --env=test doctrine:schema:create
```

Esvazie o banco de dados e recarregue todas as classes de fixtures com o comando **no ambiente de teste**:
```
php .\bin\console --env=test doctrine:fixtures:load

 Careful, database "" will be purged. Do you want to continue? (yes/no) [no]:
 > yes

   > purging database
   > loading App\DataFixtures\AppFixtures
   > loading App\DataFixtures\UserFixtures
```

Teste a atualização do banco de dados **de teste** usando o ambiente **de teste**:
```
php .\bin\console --env=test doctrine:query:sql "SELECT * FROM user"

 ---- ------------- ------- --------------------------------------------------------------
  id   email         roles   password
 ---- ------------- ------- --------------------------------------------------------------
  1    tma@cdtn.br   []      $2y$04$GTaW349Z9wwbjNrBMqg2lOHfFU.T1f0KnScwTpjXoR9r/RTYEjpG2
 ---- ------------- ------- --------------------------------------------------------------
```
# Testes específicos

Os testes tem três fases: 
1. `arrange` (inicializar o teste),
2. `act` (simular a mudança de estado do sistema), e
3. `assert` (verificar se o teste passou ou não).

## Teste unitário
Crie o teste unitário `tests/Entity/SeasonTest.php` via linha de comando (repare no uso da classe `TestCase` e no aninhamento do namespace da classe `SeasonTest` em `Entity`):
```
php .\bin\console make:test

 Which test type would you like?:
  [TestCase       ] basic PHPUnit tests
  [KernelTestCase ] basic tests that have access to Symfony services
  [WebTestCase    ] to run browser-like scenarios, but that don't execute JavaScript code
  [ApiTestCase    ] to run API-oriented scenarios
  [PantherTestCase] to run e2e scenarios, using a real-browser or HTTP client and a real web server
 > TestCase


Choose a class name for your test, like:
 * UtilTest (to create tests/UtilTest.php)
 * Service\UtilTest (to create tests/Service/UtilTest.php)
 * \App\Tests\Service\UtilTest (to create tests/Service/UtilTest.php)

 The name of the test class (e.g. BlogPostTest):
 > Entity\SeasonTest

 created: tests/Entity/SeasonTest.php

 
  Success! 
 

 Next: Open your new test class and start customizing it.
 Find the documentation at https://symfony.com/doc/current/testing.html#unit-tests
```

Escreva o teste:
```php
<?php

namespace App\Tests\Entity;

use App\Entity\Episode;
use App\Entity\Season;
use PHPUnit\Framework\TestCase;

class SeasonTest extends TestCase
{
    public function testGetWatchedEpisodes(): void
    {
        // Arrange
        $season = new Season(1);

        $episode1 = new Episode(1);
        $episode1->setWatched(true);

        $episode2 = new Episode(2);
        $episode2->setWatched(false);

        $season->addEpisode($episode1);
        $season->addEpisode($episode2);

        // Act
        $watchedEpisodes = $season->getWatchedEpisodes();

        // Assert
        // Só tem um episódio assistido...
        self::assertCount(1, $watchedEpisodes); 
        // ... e o episódio assistido é o primeiro.
        self::assertSame($episode1, $watchedEpisodes->first());
    }
}
```
Rode o teste:
```
php .\bin\phpunit
PHPUnit 9.5.22 #StandWithUkraine

Testing
.                                                                   1 / 1 (100%)

Time: 00:00.343, Memory: 10.00 MB

OK (1 test, 2 assertions)
```

## Teste de integração
Crie o teste de integração `tests/Entity/SeasonTest.php` via linha de comando (repare no uso da classe `KernelTestCase` e no aninhamento do namespace da classe `SeriesRepositoryTest` em `Repository`):
```
php .\bin\console make:test

 Which test type would you like?:
  [TestCase       ] basic PHPUnit tests
  [KernelTestCase ] basic tests that have access to Symfony services
  [WebTestCase    ] to run browser-like scenarios, but that don't execute JavaScript code
  [ApiTestCase    ] to run API-oriented scenarios
  [PantherTestCase] to run e2e scenarios, using a real-browser or HTTP client and a real web server
 > KernelTestCase


Choose a class name for your test, like:
 * UtilTest (to create tests/UtilTest.php)
 * Service\UtilTest (to create tests/Service/UtilTest.php)
 * \App\Tests\Service\UtilTest (to create tests/Service/UtilTest.php)

 The name of the test class (e.g. BlogPostTest):
 > Repository\SeriesRepositoryTest

 created: tests/Repository/SeriesRepositoryTest.php

 
  Success! 
 

 Next: Open your new test class and start customizing it.
 Find the documentation at https://symfony.com/doc/current/testing/database.html#functional-testing-of-a-doctrine-repository
```

Defina o teste em `SeriesRepositoryTest.php`:
```php
<?php

namespace App\Tests\Repository;

use App\DTO\SeriesCreationInputDTO;
use App\Repository\EpisodeRepository;
use App\Repository\SeriesRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SeriesRepositoryTest extends KernelTestCase
{
    public function testSomething(): void
    {
        // Arrange
        $kernel = self::bootKernel();

        $container = static::getContainer();
        $seriesRepository = $container->get(SeriesRepository::class);
        $episodeRepository = $container->get(EpisodeRepository::class);

        // Act
        // O método addDto não existia no curso. 
        $seriesRepository->addDto(new SeriesCreationInputDTO(
            'Series test',
            2, 
            5,
        ));

        $episodes = $episodeRepository->findAll();

        // Assert
        $this->assertSame('test', $kernel->getEnvironment());
        self::assertCount(10, $episodes);
    }
}
```
Rode o teste de integração:
```
php .\bin\phpunit

PHPUnit 9.5.22 #StandWithUkraine

Testing

Time: 00:00.265, Memory: 26.00 MB

OK (2 tests, 4 assertions)

Remaining indirect deprecation notices (1)

  1x: The "Monolog\Logger" class is considered final. It may change without further notice as of its next major version. You should not extend it from "Symfony\Bridge\Monolog\Logger".
    1x in SeriesRepositoryTest::testSomething from App\Tests\Repository
```

## Teste de aplicação
Crie o teste de integração `tests/E2E/AddButtonTest.php` via linha de comando (repare no uso da classe `WebTestCase` e no aninhamento do namespace da classe `SeriesRepositorAddButtonTest` em `E2E`):
```
php .\bin\console make:test

 Which test type would you like?:
  [TestCase       ] basic PHPUnit tests
  [KernelTestCase ] basic tests that have access to Symfony services
  [WebTestCase    ] to run browser-like scenarios, but that don't execute JavaScript code
  [ApiTestCase    ] to run API-oriented scenarios
  [PantherTestCase] to run e2e scenarios, using a real-browser or HTTP client and a real web server
 > WebTestCase


Choose a class name for your test, like:
 * UtilTest (to create tests/UtilTest.php)
 * Service\UtilTest (to create tests/Service/UtilTest.php)
 * \App\Tests\Service\UtilTest (to create tests/Service/UtilTest.php)

 The name of the test class (e.g. BlogPostTest):
 > E2E\AddButtonTest

 created: tests/E2E/AddButtonTest.php

 
  Success! 
 

 Next: Open your new test class and start customizing it.
 Find the documentation at https://symfony.com/doc/current/testing.html#functional-tests
```

Defina o teste em `AddButtonTest.php`:
```php
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
```

Rode o teste de aplicação:
```
PS D:\alura\symfony-assincrono> php .\bin\phpunit
PHPUnit 9.5.22 #StandWithUkraine

Testing 
....                                                                4 / 4 (100%)

Time: 00:00.655, Memory: 32.00 MB

OK (4 tests, 8 assertions)

Remaining indirect deprecation notices (1)

  1x: The "Monolog\Logger" class is considered final. It may change without further notice as of its next major version. You should not extend it from "Symfony\Bridge\Monolog\Logger".
    1x in AddButtonTest::testAddButtonDoesNotExistWhenUserIsNotLoggedIn from App\Tests\E2E
```

## Outros testcases do Symfony
Há ainda no Symfony os testes `ApiTestCase` (que permite testar o código de retorno e o conteúdo JSON de uma API) e `PantherTestCase` (quer permite o controle sobre o navegador real ao invés do cliente HTTP do Symfony, e que permite fazer requisições Ajax).

Para os vários componentes do Symfony (Messaging, Mailer, etc.) é possível ver algumas traits que permitem fazer alguns asserts no contexto delas (verificar se uma mensagem foi enfileirada, se um e-mail foi enviado um determinado número de vezes etc.).
