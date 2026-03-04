<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        $error = null;
        $success = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // 1. صنع token عشوائي
                $token = bin2hex(random_bytes(32));
                
                // 2. token يتحرق بعد 1 ساعة
                $expiresAt = new \DateTime('+1 hour');

                $user->setResetPasswordToken($token);
                $user->setResetPasswordExpiresAt($expiresAt);
                $em->flush();

                // 3. رابط reset
                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // 4. بعث email بـ Mailtrap
                try {
                    $emailMessage = (new Email())
                        ->from('noreply@stayzy.com')
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe - Stayzy')
                        ->html('
                            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                                <div style="background: #2e7d32; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1 style="color: white; margin: 0;">🏠 Stayzy</h1>
                                </div>
                                <div style="padding: 30px; background: #fff; border: 1px solid #eee; border-radius: 0 0 10px 10px;">
                                    <h2>Bonjour ' . $user->getPrenom() . ' !</h2>
                                    <p>Cliquez sur le bouton ci-dessous — ce lien expire dans <strong>1 heure</strong>.</p>
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="' . $resetUrl . '"
                                           style="background: #2e7d32; color: white; padding: 15px 35px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: bold; display: inline-block;">
                                            🔑 Réinitialiser mon mot de passe
                                        </a>
                                    </div>
                                </div>
                            </div>
                        ');
                    $mailer->send($emailMessage);
                } catch (\Exception $e) {
                    // smtp bloqué — on continue quand même
                }
            }

            // دايما يبيّن success حتى لو email مش موجود
            $success = true;
        }

        return $this->render('security/forgot_password.html.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // 1. تحقق من token
        $user = $userRepository->findOneBy(['resetPasswordToken' => $token]);

        // 2. تحقق إذا token انتهى
        if (!$user || $user->getResetPasswordExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Lien invalide ou expiré. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirm = $request->request->get('confirm_password');

            if (strlen($password) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                // 3. يحفظ password جديد hashé
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                
                // 4. يمسح token من DB
                $user->setResetPasswordToken(null);
                $user->setResetPasswordExpiresAt(null);
                $em->flush();

                $this->addFlash('success', '🎉 Mot de passe modifié ! Connectez-vous.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            'error' => $error,
        ]);
    }
}