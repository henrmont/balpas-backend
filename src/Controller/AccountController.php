<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Service\DataFormat;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AccountController extends AbstractController
{
    private $baseUrl = 'http://balpas.com.br/';

    #[Route('/register/account', name: 'registe_account')]
    public function registerAccount(ManagerRegistry $doctrine, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher, MailerInterface $mailer): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);
        
        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            $userEmail = $em->getRepository(User::class)->findOneBy(['email' => $request->get('username')]);

            
            if (!$userEmail) {
                $user = new User();
                $user->setEmail($request->get('username'));
                $user->setRoles(['ROLE_USER']);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,    
                        $request->get('password')
                    )
                );
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setUpdatedAt(new \DateTimeImmutable());
                $user->setIsActive(false);
                $user->setIsResident(false);
                $em->persist($user);
    
                $em->flush();

                $token = new Token();
                $token->setUser($user->getId());
                $token->setToken(md5(uniqid($user->getEmail())));
                $token->setExpireAt(new \DateTimeImmutable('+2 hours'));
                $em->persist($token);
    
                $em->flush();

                // $url = $this->baseUrl.'register/account/'.$token->getToken();

                // $email = (new TemplatedEmail())
                //     ->from(new Address('model@model.com.br', 'Model'))
                //     ->to($user->getEmail())
                //     ->subject('Please Confirm your Email')
                //     ->htmlTemplate('registration/confirmation_email.html.twig')
                //     ->context(['url' => $url])
                // ;

                // $mailer->send($email);
    
                $con->commit();

                return $this->json([
                    'message' => 'Conta criada com sucesso, um email foi enviado para confirmação.',
                    'status' => true
                ]);
            } else {
                $con->rollback();
                if (!$userEmail) {
                    return $this->json([
                        'message' => 'Usuário já está em uso',
                    ]);
                }
            }
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/get/valid/user', name: 'get_valid_user')]
    public function getValidUser(ManagerRegistry $doctrine, Request $request, DataFormat $df): Response
    {
        $request = $df->transformJsonBody($request);
        
        try {
            $em = $doctrine->getManager();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $request->get('username')]);

            if($user){
                return $this->json([
                    'valid' => $user->isVerified(),
                ]);
            } 
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/get/filtred/users', name: 'get_filtred_users')]
    public function getFiltredUsers(ManagerRegistry $doctrine, SerializerInterface $serializer, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $request = $df->transformJsonBody($request);

        try {
            $user = $doctrine->getRepository(User::class)->getFiltredUsers($request->get('user'));

            $serialized = $serializer->serialize($user,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/get/filtred/valid/user', name: 'get_filtred_valid_user')]
    public function getFiltredValidUser(ManagerRegistry $doctrine, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $request = $df->transformJsonBody($request);
        
        try {
            $em = $doctrine->getManager();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $request->get('user')]);

            if($user){
                return $this->json([
                    'valid' => true,
                ]);
            } else {
                return $this->json([
                    'valid' => false,
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/get/user/info', name: 'get_user_info')]
    public function getUserInfo(SerializerInterface $serializer, UserInterface $user): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $serialized = $serializer->serialize($user,'json');

        return JsonResponse::fromJsonString($serialized);
    }

    #[Route('/get/valid/token', name: 'get_valid_token')]
    public function getValidToken(ManagerRegistry $doctrine, Request $request, DataFormat $df, MailerInterface $mailer): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $em = $doctrine->getManager();
            $token = $em->getRepository(Token::class)->findOneBy(['token' => $request->get('token')]);

            if($token){
                if ($token->getExpireAt() >= (new DateTimeImmutable())){
                    return $this->json([
                        'valid' => $token->getToken(),
                    ]);
                } else {
                    $con->beginTransaction();
                    
                    $token->setToken(md5(uniqid('balpas.com.br')));
                    $token->setExpireAt(new \DateTimeImmutable('+2 hours'));
                    $em->persist($token);
    
                    $em->flush();

                    $con->commit();

                    $user = $em->getRepository(User::class)->find($token->getUser());
                    $url = $this->baseUrl.'register/account/'.$token->getToken();

                    $email = (new TemplatedEmail())
                        ->from(new Address('model@model.com.br', 'Model'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                        ->context(['url' => $url])
                    ;

                    $mailer->send($email);

                    return $this->json([
                        'message' => 'Link Expirado. Um novo link foi enviado.',
                    ]);
                }
            } else {
                return $this->json([
                    'message' => 'Link Inválido',
                ]);
            }
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/confirm/email', name: 'confirm_email')]
    public function confirmEmail(ManagerRegistry $doctrine, Request $request, DataFormat $df): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $token = $em->getRepository(Token::class)->findOneBy(['token' => $request->get('token')]);
            $user = $em->getRepository(User::class)->find($token->getUser());
            $user->setIsVerified(true);
            $em->persist($user);

            $em->remove($token);

            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Conta confirmada com sucesso',
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/request/reset/password', name: 'request_reset_password')]
    public function requestResetPassword(ManagerRegistry $doctrine, Request $request, DataFormat $df, MailerInterface $mailer): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);
        
        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $request->get('username')]);
            
            if ($user){
                $token = $em->getRepository(Token::class)->findBy(['user' => $user->getId()]);
                foreach ($token as $vlr) {
                    $em->remove($vlr);
                }
                
                $user->setIsVerified(false);
                $em->persist($user);
                
                $token = new Token();
                $token->setUser($user->getId());
                $token->setToken(md5(uniqid($user->getEmail())));
                $token->setExpireAt(new \DateTimeImmutable('+2 hours'));
                $em->persist($token);

                $em->flush();

                $url = $this->baseUrl.'reset/password/'.$token->getToken();

                $email = (new TemplatedEmail())
                    ->from(new Address('model@model.com.br', 'Model'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['url' => $url])
                ;

                $mailer->send($email);
                
                $con->commit();

                return $this->json([
                    'message' => 'Verifique seu email para resetar a senha.',
                    'status' => true
                ]);
            } else {
                $con->rollback();
                return $this->json([
                    'message' => 'Usuário não encontrado.',
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema'
            ]);
        }
    }

    #[Route('/reset/password', name: 'reset_password')]
    public function resetUserEmail(ManagerRegistry $doctrine, Request $request, UserPasswordHasherInterface $userPasswordHasher, DataFormat $df): Response
    {
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $token = $em->getRepository(Token::class)->findOneBy(['token' => $request->get('token')]);
            $user = $em->getRepository(User::class)->find($token->getUser());

            $user->setIsVerified(true);
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,    
                    $request->get('password')
                )
            );
            $em->persist($user);

            $em->remove($token);

            $em->flush();

            $con->commit();

            return $this->json([
                'message' => 'Password alterado com sucesso',
                'status' => true
            ]);
        } catch (\Exception $e) {
            $con->rollback();
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/set/profile/image', name: 'set_profile_image')]
    public function setProfileImage(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $profile = $em->getRepository(User::class)->find($user->getId());
            $profile->setImage($request->get('image'));
            $em->persist($profile);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Imagem alterada com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/delete/profile/image', name: 'delete_profile_image')]
    public function deleteProfileImage(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $profile = $em->getRepository(User::class)->find($user->getId());
            $profile->setImage(null);
            $em->persist($profile);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Imagem excluida com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/update/user/info', name: 'update_user_info')]
    public function updateUserInfo(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $profile = $em->getRepository(User::class)->find($user->getId());
            $profile->setName($request->get('name'));
            $profile->setPhone($request->get('phone'));
            $profile->setAddress($request->get('address'));
            $em->persist($profile);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Informações atualizadas com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/update/user/password', name: 'update_user_password')]
    public function updateUserPassword(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $profile = $em->getRepository(User::class)->find($user->getId());
            $profile->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,    
                    $request->get('password')
                )
            );
            $em->persist($profile);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Senha atualizada com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/admin/update/user/info', name: 'admin_update_user_info')]
    public function adminUpdateUserInfo(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();
            
            $user = $em->getRepository(User::class)->find($request->get('id'));
            $user->setName($request->get('name'));
            $user->setPhone($request->get('phone'));
            $user->setAddress($request->get('address'));
            $em->persist($user);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Usuário atualizado com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/admin/update/user/type', name: 'admin_update_user_type')]
    public function adminUpdateUserType(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $user = $em->getRepository(User::class)->find($request->get('id'));
            $user->setIsResident($request->get('isResident'));

            $em->persist($user);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Usuário atualizado com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/admin/update/user/valid', name: 'admin_update_user_valid')]
    public function adminUpdateUserValid(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $user = $em->getRepository(User::class)->find($request->get('id'));
            if ($user->getIsActive()) {
                $user->setIsActive(false);
                $message = 'Usuário desabilitado';
            } else {
                $user->setIsActive(true);
                $message = 'Usuário habilitado';
            }

            $em->persist($user);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/admin/update/user/roles', name: 'admin_update_user_roles')]
    public function adminUpdateUserRoles(ManagerRegistry $doctrine, UserInterface $user, Request $request, DataFormat $df, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $con = $doctrine->getConnection();
        $request = $df->transformJsonBody($request);

        try {
            $con->beginTransaction();
            $em = $doctrine->getManager();

            $user = $em->getRepository(User::class)->find($request->get('id'));
            $user->setRoles($request->get('roles'));

            $em->persist($user);
            
            $em->flush();
            
            $con->commit();
            
            return $this->json([
                'message' => 'Usuário atualizado com sucesso',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/get/admin/users', name: 'get_admin_users')]
    public function getAdminUsers(ManagerRegistry $doctrine, SerializerInterface $serializer, Request $request, DataFormat $df): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $request = $df->transformJsonBody($request);

        try {
            $user = $doctrine->getRepository(User::class)->getAdminUsers();

            $serialized = $serializer->serialize($user,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }

    #[Route('/api/get/admin/user/info/{id}', name: 'get_admin_user_info')]
    public function getAdminUserInfo(ManagerRegistry $doctrine, SerializerInterface $serializer, Request $request, DataFormat $df, $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $request = $df->transformJsonBody($request);

        try {
            $user = $doctrine->getRepository(User::class)->find($id);

            $serialized = $serializer->serialize($user,'json');

            return JsonResponse::fromJsonString($serialized);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erro no sistema',
            ]);
        }
    }
}
