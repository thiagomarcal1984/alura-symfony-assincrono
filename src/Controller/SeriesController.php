<?php

namespace App\Controller;

use App\DTO\SeriesCreationInputDTO;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use App\Form\SeriesType;
use App\Messages\SeriesWasCreated;
use App\Messages\SeriesWasDeleted;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
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

    #[Route('/series', name: 'app_series', methods: ['GET'])]
    public function seriesList(Request $request): Response
    {
        $seriesList = $this->seriesRepository->findAll();

        return $this->render('series/index.html.twig', [
            'seriesList' => $seriesList,
        ]);
    }

    #[Route('/series/create', name: 'app_series_form', methods: ['GET'])]
    public function addSeriesForm(): Response
    {
        $seriesForm = $this->createForm(SeriesType::class, new SeriesCreationInputDTO());
        return $this->renderForm('series/form.html.twig', compact('seriesForm'));
    }

    #[Route('/series/create', name: 'app_add_series', methods: ['POST'])]
    public function addSeries(Request $request): Response
    {
        $input = new SeriesCreationInputDTO();
        $seriesForm = $this->createForm(SeriesType::class, $input)
            ->handleRequest($request);

        if (!$seriesForm->isValid()) {
            return $this->renderForm('series/form.html.twig', compact('seriesForm'));
        }

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
        $series = new Series($input->seriesName);
        for ($i = 1; $i <= $input->seasonsQuantity; $i++) {
            $season = new Season($i);
            for ($j = 1; $j <= $input->episodesPerSeason; $j++) {
                $season->addEpisode(new Episode($j));
            }
            $series->addSeason($season);
        }

        $series->setCoverImagePath($newFilename);
        $this->seriesRepository->add($series, true);

        // O messenger procura os handlers para as mensagens enviadas
        // como parâmetro para o método dispatch($mensagem).
        $this->messenger->dispatch(new SeriesWasCreated($series));

        $this->addFlash(
            'success',
            "Série \"{$series->getName()}\" adicionada com sucesso"
        );

        return new RedirectResponse('/series');
    }

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

    #[Route('/series/edit/{series}', name: 'app_edit_series_form', methods: ['GET'])]
    public function editSeriesForm(Series $series): Response
    {
        $seriesForm = $this->createForm(SeriesType::class, $series, ['is_edit' => true]);
        return $this->renderForm('series/form.html.twig', compact('seriesForm', 'series'));
    }

    #[Route('/series/edit/{series}', name: 'app_store_series_changes', methods: ['PATCH'])]
    public function storeSeriesChanges(Series $series, Request $request): Response
    {
        $seriesForm = $this->createForm(SeriesType::class, $series, ['is_edit' => true]);
        $seriesForm->handleRequest($request);

        if (!$seriesForm->isValid()) {
            return $this->renderForm('series/form.html.twig', compact('seriesForm', 'series'));
        }

        $this->addFlash('success', "Série \"{$series->getName()}\" editada com sucesso");
        $this->entityManager->flush();

        return new RedirectResponse('/series');
    }
}
