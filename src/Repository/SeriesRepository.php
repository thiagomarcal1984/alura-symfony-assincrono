<?php

namespace App\Repository;

use App\DTO\SeriesCreationInputDTO;
use App\Entity\Episode;
use App\Entity\Season;
use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Series>
 *
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function addDto(SeriesCreationInputDTO $dto): Series
    {
        $series = new Series($dto->seriesName);
        for ($i = 1; $i <= $dto->seasonsQuantity; $i++) {
            $season = new Season($i);
            for ($j = 1; $j <= $dto->episodesPerSeason; $j++) {
                $season->addEpisode(new Episode($j));
            }
            $series->addSeason($season);
        }

        $series->setCoverImagePath($dto->coverImage);

        $this->add($series, true);

        return $series;
    }
    
    public function add(Series $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Series $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeById(int $id): void
    {
        $series = $this->getEntityManager()->getPartialReference(Series::class, $id);
        $this->remove($series, true);
    }

//    /**
//     * @return Series[] Returns an array of Series objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Series
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
