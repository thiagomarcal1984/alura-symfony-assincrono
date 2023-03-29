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
