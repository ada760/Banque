<?php

namespace App\Http\Controllers;

use App\Services\OmAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Authentification OM Pay"
 * )
 */

class OmAuthController extends Controller
{
    private OmAuthService $omAuthService;

    public function __construct(OmAuthService $omAuthService)
    {
        $this->omAuthService = $omAuthService;
    }

    /**
     * @OA\Post(
     *     path="/auth/request-otp",
     *     summary="Demander un OTP pour l'authentification",
     *     description="Envoie un code OTP par email pour l'authentification OM Pay",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone Orange Money")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP envoyé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code OTP envoyé par email"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_in", type="integer", example=360)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation ou compte bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Numéro de téléphone invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{9,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Numéro de téléphone invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->omAuthService->requestOtp($request->phone);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'expires_in' => $result['expires_in']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/verify-otp",
     *     summary="Vérifier le code OTP",
     *     description="Vérifie le code OTP reçu par email et indique l'étape suivante",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","otp"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="otp", type="string", example="123456", description="Code OTP à 6 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP vérifié avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="has_secret_code", type="boolean", example=false),
     *                 @OA\Property(property="next_step", type="string", example="set_secret_code")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code OTP incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code OTP incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{9,15}$/',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $isValid = $this->omAuthService->verifyOtp($request->phone, $request->otp);

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP incorrect'
                ], 401);
            }

            // Vérifier si le client a déjà un code secret
            $hasSecretCode = $this->omAuthService->hasSecretCode($request->phone);

            return response()->json([
                'success' => true,
                'message' => 'OTP vérifié avec succès',
                'data' => [
                    'has_secret_code' => $hasSecretCode,
                    'next_step' => $hasSecretCode ? 'login' : 'set_secret_code'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/set-secret-code",
     *     summary="Définir le code secret OM Pay",
     *     description="Définit le code secret à 4 chiffres pour l'authentification OM Pay (première connexion ou reconnexion)",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","secret_code"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="secret_code", type="string", example="1234", description="Code secret à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code secret défini avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code secret défini avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                     @OA\Property(property="telephone", type="string", example="772687847"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                         @OA\Property(property="titulaire", type="string", example="Abdoulaye Seck"),
     *                         @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                     )
     *                 ),
     *                 @OA\Property(property="is_first_login", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur lors de la définition du code secret",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Un code secret OM Pay est déjà défini pour ce numéro. Utilisez la connexion normale.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function setSecretCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{9,15}$/',
            'secret_code' => 'required|string|regex:/^[0-9]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->omAuthService->setSecretCode($request->phone, $request->secret_code);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'token' => $result['token'],
                    'token_type' => $result['token_type'],
                    'client' => [
                        'id' => $result['client']->id,
                        'telephone' => $result['client']->telephone,
                        'user' => [
                            'id' => $result['client']->user->id,
                            'titulaire' => $result['client']->user->titulaire,
                            'email' => $result['client']->user->email
                        ]
                    ],
                    'is_first_login' => $result['is_first_login']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="Connexion avec code secret OM Pay",
     *     description="Authentification avec le code secret à 4 chiffres pour les connexions suivantes",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone","secret_code"},
     *             @OA\Property(property="phone", type="string", example="772687847", description="Numéro de téléphone"),
     *             @OA\Property(property="secret_code", type="string", example="1234", description="Code secret à 4 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="string", example="10281d44-9f2d-3008-8c61-7d55a71b538f"),
     *                     @OA\Property(property="telephone", type="string", example="772687847"),
     *                     @OA\Property(property="user", type="object",
     *                         @OA\Property(property="id", type="string", example="a17b046a-0da9-3d17-ba26-978f3ea3a1e3"),
     *                         @OA\Property(property="titulaire", type="string", example="Abdoulaye Seck"),
     *                         @OA\Property(property="email", type="string", example="seckmoustapha238@gmail.com")
     *                     )
     *                 ),
     *                 @OA\Property(property="compte_actif", type="object",
     *                     @OA\Property(property="id", type="string", example="62028976-381e-3a70-9073-df6de0fd5e74"),
     *                     @OA\Property(property="num_compte", type="string", example="C0081999796"),
     *                     @OA\Property(property="solde", type="number", format="float", example=8728.00),
     *                     @OA\Property(property="devise", type="string", example="XOF")
     *                 ),
     *                 @OA\Property(property="is_first_login", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Code secret incorrect ou compte bloqué",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Code secret incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{9,15}$/',
            'secret_code' => 'required|string|regex:/^[0-9]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->omAuthService->loginWithSecretCode($request->phone, $request->secret_code);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'token' => $result['token'],
                    'token_type' => $result['token_type'],
                    'client' => [
                        'id' => $result['client']->id,
                        'telephone' => $result['client']->telephone,
                        'user' => [
                            'id' => $result['client']->user->id,
                            'titulaire' => $result['client']->user->titulaire,
                            'email' => $result['client']->user->email
                        ]
                    ],
                    'compte_actif' => [
                        'id' => $result['compte_actif']->id,
                        'num_compte' => $result['compte_actif']->num_compte,
                        'solde' => $result['compte_actif']->solde,
                        'devise' => $result['compte_actif']->devise
                    ],
                    'is_first_login' => $result['is_first_login']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): JsonResponse
    {
        try {
            $this->omAuthService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Vérifier le statut d'authentification
     */
    public function checkAuth(): JsonResponse
    {
        $client = $this->omAuthService->getCurrentClient();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $compteActif = $client->compte->where('status', 'actif')->first();

        return response()->json([
            'success' => true,
            'message' => 'Authentifié',
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'telephone' => $client->telephone,
                    'user' => [
                        'id' => $client->user->id,
                        'titulaire' => $client->user->titulaire,
                        'email' => $client->user->email
                    ]
                ],
                'compte_actif' => $compteActif ? [
                    'id' => $compteActif->id,
                    'num_compte' => $compteActif->num_compte,
                    'solde' => $compteActif->solde,
                    'devise' => $compteActif->devise
                ] : null
            ]
        ]);
    }
}
