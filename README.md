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
