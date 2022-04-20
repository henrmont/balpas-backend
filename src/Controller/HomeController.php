<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Plantao;
use App\Entity\Taxe;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class HomeController extends AbstractController
{
    #[Route('/get/dashboard/chart/data', name: 'get_dashboard_chart_data')]
    public function getDashboardChartData(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $data = [];
            array_push($data, ['name'=>"Líquido",'value'=>0]);
            array_push($data, ['name'=>"Impostos",'value'=>0]);
            array_push($data, ['name'=>"Fatura",'value'=>0]);

            $plantao = $doctrine->getRepository(Plantao::class)->getDashboardData($user->getEmail());
            
            $plantoes = [];
            foreach ($plantao as $vlr) {
                array_push($plantoes, $vlr['id']);
                $data[0]['value'] = round($data[0]['value'],2) + round($vlr['value'],2);
            }
            
            $imposto = $doctrine->getRepository(Taxe::class)->getTaxeForPlantao($plantoes);
            
            if ($imposto) {
                foreach ($imposto as $vlr) {
                    if ($vlr['type']) {
                        $data[0]['value'] = round($data[0]['value'],2) - round($vlr['pvalue']*($vlr['value']/100),2);
                        $data[1]['value'] = round($data[1]['value'],2) + round($vlr['pvalue']*($vlr['value']/100),2);
                    } else {
                        $data[0]['value'] = round($data[0]['value'],2) - round($vlr['value'],2);
                        $data[1]['value'] = round($data[1]['value'],2) + round($vlr['value'],2);
                    }
                }
            }

            $fatura = $doctrine->getRepository(Invoice::class)->getCurrentInvoice($user->getEmail());

            $data[2]['value'] = round($fatura[0]['value'],2);
            
            $serialized = $serializer->serialize($data,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }

    #[Route('/get/values/data', name: 'get_values_data')]
    public function getValuesData(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $data = [];
            array_push($data, ['name'=>"Líquido",'value'=>0]);
            array_push($data, ['name'=>"Impostos",'value'=>0]);

            $plantao = $doctrine->getRepository(Plantao::class)->getDashboardData($user->getEmail());
            
            $plantoes = [];
            foreach ($plantao as $vlr) {
                array_push($plantoes, $vlr['id']);
                $data[0]['value'] = round($data[0]['value'],2) + round($vlr['value'],2);
            }
            
            $imposto = $doctrine->getRepository(Taxe::class)->getTaxeForPlantao($plantoes);
            
            if ($imposto) {
                foreach ($imposto as $vlr) {
                    if ($vlr['type']) {
                        $data[0]['value'] = round($data[0]['value'],2) - round($vlr['pvalue']*($vlr['value']/100),2);
                        $data[1]['value'] = round($data[1]['value'],2) + round($vlr['pvalue']*($vlr['value']/100),2);
                    } else {
                        $data[0]['value'] = round($data[0]['value'],2) - round($vlr['value'],2);
                        $data[1]['value'] = round($data[1]['value'],2) + round($vlr['value'],2);
                    }
                }
            }

            $serialized = $serializer->serialize($data,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }

    #[Route('/get/admin/dashboard/chart/data', name: 'get_admin_dashboard_chart_data')]
    public function getAdminDashboardChartData(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $data = [];
            array_push($data, ['name'=>"Pagas",'value'=>0]);
            array_push($data, ['name'=>"Pendentes",'value'=>0]);

            $invoices = $doctrine->getRepository(Invoice::class)->getMonthInvoices();

            foreach ($invoices as $vlr) {
                if($vlr['isPaid']) {
                    $data[0]['value'] = round($data[0]['value'],2) + round($vlr['value'],2);
                } else {
                    $data[1]['value'] = round($data[1]['value'],2) + round($vlr['value'],2);
                }
            }
            
            $serialized = $serializer->serialize($data,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }
}
