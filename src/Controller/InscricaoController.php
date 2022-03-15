<?php

namespace App\Controller;

use App\Entity\Inscricao;
use App\Service\DataFormat;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InscricaoController extends AbstractController
{
    #[Route('/register/inscricao', name: 'register_inscricao')]
    public function registerInscricao(ManagerRegistry $doctrine, DataFormat $df, Request $request): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $inscricao = new Inscricao();
            $inscricao->setNome($request->get('nome'));
            $inscricao->setEmail($request->get('email'));
            $inscricao->setTelefone($request->get('telefone'));
            $em->persist($inscricao);
            
            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Inscrição realizada com sucesso.',
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }
}
