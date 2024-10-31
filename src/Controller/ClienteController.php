<?php

namespace App\Controller;

use App\Repository\UbicacionRepository;
use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


class ClienteController extends AbstractController
{

    #[Route('/cliente', name: 'app_cliente', methods: ['GET'])]
    public function index(UsuarioRepository $usuarioRepository): Response
    {
        return $this->convertToJson($usuarioRepository->findAll());
    }

    #[Route('/ubicaciones', name: 'app_get_ubicaciones', methods: ['GET'])]
    public function getUbicaciones(UbicacionRepository $ubicacionRepository): JsonResponse
    {
        return $this->convertToJson($ubicacionRepository->findAll());
    }

    #[Route('/loginCliente', name: 'app_loginCliente', methods: ['PUT'])]
    public function login(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar el usuario por nombre de usuario
        $usuario = $usuarioRepository->findOneBy(['email' => $data['email']]);

        if (!$usuario || $usuario->getPassword() !== $data['password']) {
            return new JsonResponse(['status' => 'Credenciales inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'status' => 'Inicio de sesión exitoso',
            'user' => [
                'id' => $usuario->getId(),
                'email' => $usuario->getEmail(),
                'nombre' => $usuario->getNombre(),
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/guardarUbicacion', name: 'app_guardar_ubicacion', methods: ['POST'])]
    public function guardarUbicacion(Request $request, UsuarioRepository $usuarioRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validar que se proporcionen los parámetros necesarios
        if (empty($data['idUsuario']) || empty($data['latitud']) || empty($data['longitud'])) {
            return new JsonResponse(['status' => 'Faltan parámetros'], Response::HTTP_BAD_REQUEST);
        }

        // Obtener el usuario actualmente autenticado
        $usuario = $usuarioRepository->find($data['idUsuario']);

        if (!$usuario) {
            return new JsonResponse(['status' => 'Usuario no encontrado'], Response::HTTP_UNAUTHORIZED);
        }

        $usuarioRepository->guardarUbicacion($usuario, $data['latitud'], $data['longitud']);

        return new JsonResponse(['status' => 'Ubicación guardada correctamente'], Response::HTTP_CREATED);
    }



    private function convertToJson($data): JsonResponse
    {
        // Configuramos los encoders y normalizadores
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];

        // Creamos el servicio Serializer
        $serializer = new Serializer($normalizers, $encoders);

        // Normalizamos los datos
        $normalized = $serializer->normalize($data, null, [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
            ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId(); // Para evitar problemas de referencia circular
            }
        ]);

        // Convertimos los datos normalizados a JSON
        $jsonContent = $serializer->serialize($normalized, 'json');

        // Retornamos la respuesta en formato JSON
        return JsonResponse::fromJsonString($jsonContent, 200);
    }


}
