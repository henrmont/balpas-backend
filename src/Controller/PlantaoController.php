<?php

namespace App\Controller;

use App\Entity\Plantao;
use App\Entity\Taxe;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class PlantaoController extends AbstractController
{
    #[Route('/admin/register/plantao', name: 'admin_register_plantao')]
    public function adminRegisterPlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $plantao = new Plantao();
            $plantao->setType($request->get('type'));
            $plantao->setOther($request->get('other'));
            $plantao->setLocal($request->get('local'));
            $plantao->setStartAt(new \DateTimeImmutable(str_split($request->get('dstart'),10)[0].' '.$request->get('hstart').':00'));
            $plantao->setDuration($request->get('duration'));
            $plantao->setValue($request->get('value'));
            $plantao->setCompany($request->get('company'));
            $plantao->setCreatedAt(new \DateTimeImmutable());
            $plantao->setUpdatedAt(new \DateTimeImmutable());
            $plantao->setPrivate(false);
            $plantao->setIsValid(false);
            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão cadastrado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/get/plantoes', name: 'admin_get_plantoes')]
    public function getAdminPlantoes(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $plantoes = $doctrine->getRepository(Plantao::class)->findBy([
            'private' => false
        ],[
            'id' => 'DESC'
        ]);

        $serialized = $serializer->serialize($plantoes,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/get/plantao/{id}', name: 'get_plantao')]
    public function getPlantao(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $plantao = $doctrine->getRepository(Plantao::class)->find($id);

        $serialized = $serializer->serialize($plantao,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/admin/edit/plantao', name: 'admin_edit_plantao')]
    public function adminEditPlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            $plantao->setType($request->get('type'));
            $plantao->setOther($request->get('other'));
            $plantao->setLocal($request->get('local'));
            $plantao->setStartAt(new \DateTimeImmutable(str_split($request->get('dstart'),10)[0].' '.$request->get('hstart').':00'));
            $plantao->setDuration($request->get('duration'));
            $plantao->setValue($request->get('value'));
            $plantao->setCompany($request->get('company'));
            $plantao->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão alterado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/delete/plantao', name: 'admin_delete_plantao')]
    public function adminDeletePlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            
            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            $em->remove($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/validate/plantao', name: 'admin_validate_plantao')]
    public function adminValidatePlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            if ($plantao->getIsValid()) {
                $plantao->setIsValid(false);
                $message = 'Plantão invalidado';
            } else {
                $plantao->setIsValid(true);
                $message = 'Plantão validado';
            }
            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/admin/status/plantao', name: 'admin_status_plantao')]
    public function adminStatusPlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            $plantao->setNote($request->get('note'));
            $plantao->setPayment($request->get('payment'));

            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Status do pagamento definido',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/get/plantao/value/{id}', name: 'get_plantao_value')]
    public function getPlantaoValue(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $value = [];

        $plantao = $doctrine->getRepository(Plantao::class)->find($id);
        $liquido = [
            'name'  => 'Líquido',
            'value' => round($plantao->getValue(),2)
        ];
        
        $impostos = $doctrine->getRepository(Taxe::class)->findBy([
            'plantao_id' => $id
        ]);

        foreach ($impostos as $vlr) {
            if ($vlr->getType()) {
                $imposto = [
                    'name'  => $vlr->getName(),
                    'value' => round(($vlr->getValue()*$plantao->getValue())/100,2)
                ];
                $liquido['value'] = $liquido['value'] - round(($vlr->getValue()*$plantao->getValue())/100,2);
                array_push($value,$imposto);
            } else {
                $imposto = [
                    'name'  => $vlr->getName(),
                    'value' => round($vlr->getValue(),2)
                ];
                $liquido['value'] = $liquido['value'] - round($vlr->getValue(),2);
                array_push($value,$imposto);
            }
        }

        array_push($value,$liquido);

        $serialized = $serializer->serialize($value,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/get/plantoes', name: 'get_plantoes')]
    public function getPlantoes(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $plantoes = $doctrine->getRepository(Plantao::class)->getPlantoes($user->getEmail());

        foreach ($plantoes as $chv => $vlr1) {
            $value = [];
    
            $liquido = [
                'name'  => 'Líquido',
                'value' => round($vlr1['value'],2)
            ];
            
            $impostos = $doctrine->getRepository(Taxe::class)->findBy([
                'plantao_id' => $vlr1['id']
            ]);
    
            foreach ($impostos as $vlr2) {
                if ($vlr2->getType()) {
                    $imposto = [
                        'name'  => $vlr2->getName(),
                        'value' => round(($vlr2->getValue()*$vlr1['value'])/100,2)
                    ];
                    $liquido['value'] = $liquido['value'] - round(($vlr2->getValue()*$vlr1['value'])/100,2);
                    array_push($value,$imposto);
                } else {
                    $imposto = [
                        'name'  => $vlr2->getName(),
                        'value' => round($vlr2->getValue(),2)
                    ];
                    $liquido['value'] = $liquido['value'] - round($vlr2->getValue(),2);
                    array_push($value,$imposto);
                }
            }
    
            array_push($value,$liquido);
            array_push($plantoes[$chv],$value);
        }

        $serialized = $serializer->serialize($plantoes,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/delete/plantao', name: 'delete_plantao')]
    public function deletePlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            $plantao->setUser(null);

            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/get/pega/plantoes', name: 'get_pega_plantoes')]
    public function getPegaPlantoes(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $plantoes = $doctrine->getRepository(Plantao::class)->getPegaPlantoes();

        foreach ($plantoes as $chv => $vlr1) {
            $value = [];
    
            $liquido = [
                'name'  => 'Líquido',
                'value' => round($vlr1['value'],2)
            ];
            
            $impostos = $doctrine->getRepository(Taxe::class)->findBy([
                'plantao_id' => $vlr1['id']
            ]);
    
            foreach ($impostos as $vlr2) {
                if ($vlr2->getType()) {
                    $imposto = [
                        'name'  => $vlr2->getName(),
                        'value' => round(($vlr2->getValue()*$vlr1['value'])/100,2)
                    ];
                    $liquido['value'] = $liquido['value'] - round(($vlr2->getValue()*$vlr1['value'])/100,2);
                    array_push($value,$imposto);
                } else {
                    $imposto = [
                        'name'  => $vlr2->getName(),
                        'value' => round($vlr2->getValue(),2)
                    ];
                    $liquido['value'] = $liquido['value'] - round($vlr2->getValue(),2);
                    array_push($value,$imposto);
                }
            }
    
            array_push($value,$liquido);
            array_push($plantoes[$chv],$value);
        }

        $serialized = $serializer->serialize($plantoes,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/pega/plantao', name: 'pega_plantao')]
    public function pegaPlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $plantao = $em->getRepository(Plantao::class)->find($request->get('id'));
            $plantao->setUser($user->getEmail());

            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão adicionado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/register/plantao', name: 'register_plantao')]
    public function userRegisterPlantao(ManagerRegistry $doctrine, UserInterface $user, DataFormat $df, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $plantao = new Plantao();
            $plantao->setType($request->get('type'));
            $plantao->setOther($request->get('other'));
            $plantao->setLocal($request->get('local'));
            $plantao->setStartAt(new \DateTimeImmutable(str_split($request->get('dstart'),10)[0].' '.$request->get('hstart').':00'));
            $plantao->setDuration($request->get('duration'));
            $plantao->setValue($request->get('value'));
            $plantao->setCompany($request->get('company'));
            $plantao->setCreatedAt(new \DateTimeImmutable());
            $plantao->setUpdatedAt(new \DateTimeImmutable());
            $plantao->setPrivate(true);
            $plantao->setIsValid(true);
            $plantao->setUser($user->getEmail());
            $em->persist($plantao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Plantão cadastrado com sucesso',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Email inválido',
            ]);
        }
    }

    #[Route('/get/plantoes/schedule', name: 'get_plantoes_schedule')]
    public function getPlantoesSchedule(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $plantao = $doctrine->getRepository(Plantao::class)->getPlantoesSchedule($user->getEmail());

            $serialized = $serializer->serialize($plantao,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }

    #[Route('/get/today/plantoes/schedule/{date}', name: 'get_today_plantoes_schedule')]
    public function getTodayPlantoesSchedule(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user, $date): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $date = str_split($date, 10);

            $today = $doctrine->getRepository(Plantao::class)->getTodayPlantoesSchedule($user->getEmail(), $date[0]);

            $serialized = $serializer->serialize($today,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }

    #[Route('/get/latest/plantao', name: 'get_latest_plantao')]
    public function getLatestPlantao(ManagerRegistry $doctrine, SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            // $date = (new \DateTimeImmutable());

            $today = $doctrine->getRepository(Plantao::class)->getLatestPlantao($user->getEmail());

            $serialized = $serializer->serialize($today,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Email inválido',
                'status' => 0
            ]);
        }
    }
}
