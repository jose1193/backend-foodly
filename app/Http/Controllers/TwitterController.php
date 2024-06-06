<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Abraham\TwitterOAuth\TwitterOAuth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;


class TwitterController extends Controller
{
   
    
    public function redirectToTwitter()
    {
        try {
            $consumerKey = env('TWITTER_CONSUMER_KEY');
            $consumerSecret = env('TWITTER_CONSUMER_SECRET');
            $callbackUrl = env('TWITTER_CALLBACK_URL');

            // Verificar si las variables de entorno se cargaron correctamente
            if (!$consumerKey || !$consumerSecret || !$callbackUrl) {
                throw new \Exception('Twitter API keys are not set correctly in the .env file.');
            }

            $twitterOAuth = new TwitterOAuth($consumerKey, $consumerSecret);

            $requestToken = $twitterOAuth->oauth('oauth/request_token', [
                'oauth_callback' => $callbackUrl
            ]);

            if ($twitterOAuth->getLastHttpCode() != 200) {
                throw new \Exception('Could not connect to Twitter. Refresh the page or try again later.');
            }

            $url = $twitterOAuth->url('oauth/authenticate', [
                'oauth_token' => $requestToken['oauth_token']
            ]);

            // Generar una URL que intente abrir la aplicación móvil de Twitter primero
            $mobileUrl = 'twitter://oauth?oauth_token=' . $requestToken['oauth_token'];
            $webUrl = $url;

            return response()->json([
                'oauth_token' => $requestToken['oauth_token'],
                'oauth_token_secret' => $requestToken['oauth_token_secret'],
                'mobile_url' => $mobileUrl,
                'web_url' => $webUrl
            ]);
        } catch (TwitterOAuthException $e) {
            Log::error('TwitterOAuthException: ' . $e->getMessage());
            return response()->json(['error' => 'Twitter API is temporarily unavailable. Please try again later.'], 503);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }



     public function handleTwitterCallback(Request $request)
    {
        try {
            $oauthToken = $request->input('oauth_token');
            $oauthVerifier = $request->input('oauth_verifier');

            $twitterOAuth = new TwitterOAuth(
                env('TWITTER_CONSUMER_KEY'),
                env('TWITTER_CONSUMER_SECRET'),
                $oauthToken,
                $request->input('oauth_token_secret')
            );

            $accessToken = $twitterOAuth->oauth("oauth/access_token", [
                "oauth_verifier" => $oauthVerifier
            ]);

            // Ahora usar el access token para obtener la información adicional del usuario
            $twitterOAuth = new TwitterOAuth(
                env('TWITTER_CONSUMER_KEY'),
                env('TWITTER_CONSUMER_SECRET'),
                $accessToken['oauth_token'],
                $accessToken['oauth_token_secret']
            );

            $user = $twitterOAuth->get('account/verify_credentials', [
                'include_email' => 'true',
                'skip_status' => 'true',
                'include_entities' => 'false'
            ]);

            // Devolver la información del usuario junto con el access token como respuesta JSON
            return response()->json([
                'access_token' => $accessToken,
                'user' => $user
            ]);
        } catch (TwitterOAuthException $e) {
            Log::error('TwitterOAuthException: ' . $e->getMessage());
            return response()->json(['error' => 'Twitter API is temporarily unavailable. Please try again later.'], 503);
        } catch (\Exception $e) {
            Log::error('Exception: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

}
