<?php

namespace App\Controller;

use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;


class TecnicoController extends AbstractController
{
    #[Route('/loginTecnicos', name: 'app_tecnico')]
    public function index(UsuarioRepository $usuarioRepository)
    {
        //$tecnico = $usuarioRepository->findBy(['tipo' => 'tecnico']);

        return $this->render('tecnicos/index.html.twig');
    }

    //login de aplicacion de tecnicos
    #[Route('/comprobarLogin', name: 'app_login', method: ['POST'])]
    public function login(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario por email de usuario
        $usuario = $usuarioRepository->findOneBy(['email' => $data['email']]);

        if (!$usuario || $usuario->getPassword() !== $data['password']) {
            return new JsonResponse(['status' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        // Verificar si el rol del usuario es tecnicoC o tecnicoM
        if ($usuario->getRol() !== 'tecnicoC' && $usuario->getRol() !== 'tecnicoM') {
            return new JsonResponse(['status' => 'Persona no autorizada'], Response::HTTP_FORBIDDEN);
        }
        

        return new JsonResponse(['status' => 'Autenticación exitosa'], Response::HTTP_OK);
    }
    

}
