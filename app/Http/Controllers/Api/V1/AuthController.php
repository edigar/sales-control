<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @param  LoginUserRequest $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credentials are invalid'], Response::HTTP_UNAUTHORIZED);
            }

            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            Log::error('Authentication failed', [
                'credentials' => $credentials,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'request' => $request->all(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Authentication failed',
            ]);

            return response()->json(['error' => 'Authentication failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = UserResource::make(auth('api')->user());

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error('Unauthorized', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Unauthorized',
            ]);

            return response()->json(['error' => 'Unauthorized'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Logout failed',
            ]);

            return response()->json(['error' => 'Logout failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            return $this->respondWithToken(auth('api')->refresh());
        } catch (\Exception $e) {
            Log::error('Refresh token failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'http_message' => 'Refresh token failed',
            ]);

            return response()->json(['error' => 'Refresh token failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return JsonResponse
     */
    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL()
        ]);
    }
}
