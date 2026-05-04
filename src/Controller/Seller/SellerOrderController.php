<?php

namespace App\Controller\Seller;

use App\Repository\OrderItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SellerOrderController extends AbstractController
{
    #[Route('/seller/orders', name: 'seller_orders')]
    public function index(
        Request $request,
        OrderItemRepository $orderItemRepository
    ): Response {
        $seller = $this->getUser();

        $period = $request->query->get('period');
        $startDateInput = $request->query->get('startDate');
        $endDateInput = $request->query->get('endDate');

        $startDate = null;
        $endDate = null;

        if ($startDateInput) {
            $startDate = new \DateTimeImmutable($startDateInput . ' 00:00:00');
        }

        if ($endDateInput) {
            $endDate = new \DateTimeImmutable($endDateInput . ' 23:59:59');
        }

        if (!$startDate && !$endDate && $period) {
            $now = new \DateTimeImmutable();

            match ($period) {
                'day' => [
                    $startDate = $now->setTime(0, 0, 0),
                    $endDate = $now->setTime(23, 59, 59),
                ],
                'week' => [
                    $startDate = $now->modify('monday this week')->setTime(0, 0, 0),
                    $endDate = $now->modify('sunday this week')->setTime(23, 59, 59),
                ],
                'month' => [
                    $startDate = $now->modify('first day of this month')->setTime(0, 0, 0),
                    $endDate = $now->modify('last day of this month')->setTime(23, 59, 59),
                ],
                'year' => [
                    $startDate = $now->modify('first day of january this year')->setTime(0, 0, 0),
                    $endDate = $now->modify('last day of december this year')->setTime(23, 59, 59),
                ],
                default => null,
            };
        }

        $queryBuilder = $orderItemRepository->createQueryBuilder('oi')
            ->join('oi.orderEntity', 'o')
            ->join('oi.product', 'p')
            ->where('oi.seller = :seller')
            ->setParameter('seller', $seller)
            ->orderBy('o.createdAt', 'DESC');

        if ($startDate) {
            $queryBuilder
                ->andWhere('o.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $queryBuilder
                ->andWhere('o.createdAt <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        $items = $queryBuilder
            ->getQuery()
            ->getResult();

        $totalGross = 0;
        $totalCommission = 0;
        $totalNet = 0;
        $totalProductsSold = count($items);

        foreach ($items as $item) {
            $price = (float) $item->getProductPrice();
            $commission = (float) $item->getCommissionAmount();
            $net = (float) $item->getSellerAmount();

            $totalGross += $price;
            $totalCommission += $commission;
            $totalNet += $net;
        }

        return $this->render('seller/orders/index.html.twig', [
            'items' => $items,
            'totalGross' => $totalGross,
            'totalCommission' => $totalCommission,
            'totalNet' => $totalNet,
            'totalProductsSold' => $totalProductsSold,
            'period' => $period,
            'startDate' => $startDateInput,
            'endDate' => $endDateInput,
        ]);
    }
}