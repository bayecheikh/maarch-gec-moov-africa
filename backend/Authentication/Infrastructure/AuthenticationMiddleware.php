<?php

/**
 * Copyright Maarch since 2008 under license GPLv3.
 * See the LICENSE.txt file in the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Authentication Middleware class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Authentication\Infrastructure;

use Configuration\models\ConfigurationModel;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MaarchCourrier\Authentication\Application\ApiAuthentication;
use MaarchCourrier\Core\Infrastructure\Environment;
use MaarchCourrier\Core\Infrastructure\TokenService;
use MaarchCourrier\User\Infrastructure\Repository\ApiTokenRepository;
use MaarchCourrier\User\Infrastructure\Repository\UserRepository;
use phpCAS;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use SrcCore\controllers\AuthenticationController;
use SrcCore\controllers\CoreController;
use SrcCore\controllers\LogsController;
use SrcCore\controllers\UrlController;
use SrcCore\http\Response;
use SrcCore\models\CoreConfigModel;
use stdClass;

class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = new Response();

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $route->getPattern();

        $currentMethod = $route->getMethods()[0];
        $currentRoute = $route->getPattern();

        $control = AuthenticationMiddleware::middlewareControl($request, $currentMethod, $currentRoute);
        if (!empty($control)) {
            return $response->withStatus($control['code'])->withJson(['errors' => $control['errors']]);
        }

        return $handler->handle($request);
    }

    /**
     * Controls middleware authentication for a given request.
     *
     * This method checks if the current route requires authentication. If the route does not require
     * authentication or the authentication is successful, it returns an empty array.
     *
     * If authentication fails or the authenticated user does not have permission to access the route,
     * the method returns an associative array containing:
     * - 'code': an HTTP status code (e.g., 401 for authentication failure or 403 for forbidden access),
     * - 'errors': an error message explaining why access was denied.
     *
     * @param ServerRequestInterface $request The incoming request.
     * @param string $currentHttpMethod The HTTP method of the current request.
     * @param string $currentRoute The current route being accessed.
     * @return array Returns an empty array if authentication is successful; otherwise, returns an array
     *               with the HTTP status code and corresponding error message(s).
     * @throws Exception If an error occurs during processing.
     */
    public static function middlewareControl(
        ServerRequestInterface $request,
        string $currentHttpMethod,
        string $currentRoute
    ): array {
        $return = [];
        if (!in_array($currentHttpMethod . $currentRoute, AuthenticationController::ROUTES_WITHOUT_AUTHENTICATION)) {
            if (
                !AuthenticationController::canAccessInstallerWithoutAuthentication([
                    'route' => $currentHttpMethod . $currentRoute
                ])
            ) {
                $authorizationHeaders = $request->getHeader('Authorization');
                $user = (new ApiAuthentication(
                    new Environment(),
                    new UserRepository(),
                    new AuthenticateService(),
                    new TokenService(),
                    new ApiTokenRepository(new UserRepository())
                ))->execute($authorizationHeaders);

                if (!empty($user)) {
                    CoreController::setGlobals(['userId' => $user->getId()]);

                    $ssoValidation = self::validateSSOJWTCorrespondence($request, $authorizationHeaders);
                    if (!$ssoValidation['valid']) {
                        return ['code' => 401, 'errors' => $ssoValidation['error']];
                    }

                    if (!empty($currentRoute)) {
                        $check = AuthenticationController::isRouteAvailable([
                            'userId'        => $user->getId(),
                            'currentRoute'  => $currentRoute,
                            'currentMethod' => $currentHttpMethod
                        ]);
                        if (!$check['isRouteAvailable']) {
                            $return = ['code' => 403, 'errors' => $check['errors']];
                        }
                    }
                } else {
                    $return = ['code' => 401, 'errors' => 'Authentication Failed'];
                }
            }
        }

        return $return;
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $authorizationHeaders
     * @return array
     * @throws Exception
     */
    private static function validateSSOJWTCorrespondence(
        ServerRequestInterface $request,
        array $authorizationHeaders
    ): array {
        $loggingMethod = CoreConfigModel::getLoggingMethod();

        if (in_array($loggingMethod['id'], ['standard', 'ldap', 'azure_saml', 'sso'])) {
            return ['valid' => true];
        }

        $jwt = self::extractJWTFromHeaders($authorizationHeaders);
        if (!$jwt) {
            return ['valid' => false, 'error' => 'JWT token not found'];
        }

        try {
            $jwtPayload = self::decodeJWT($jwt);

            $currentSSOIdentity = self::getCurrentSSOIdentity($request, $loggingMethod['id']);

            if ($currentSSOIdentity === null) {
                return ['valid' => true];
            }

            $jwtSSOIdentity = $jwtPayload['sso_identity'] ?? null;

            if ($jwtSSOIdentity === null) {
                return ['valid' => false, 'error' => 'SSO identity not found in JWT'];
            }

            $currentIdentityNormalized = strtolower(trim($currentSSOIdentity));
            $jwtIdentityNormalized = strtolower(trim($jwtSSOIdentity));

            if ($currentIdentityNormalized !== $jwtIdentityNormalized) {
                return ['valid' => false, 'error' => 'SSO/JWT identity mismatch'];
            }

            return ['valid' => true];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'JWT validation error: ' . $e->getMessage()];
        }
    }

    /**
     * @param array $authorizationHeaders
     * @return string|null
     */
    private static function extractJWTFromHeaders(array $authorizationHeaders): ?string
    {
        if (empty($authorizationHeaders)) {
            return null;
        }

        $authHeader = $authorizationHeaders[0];
        if (str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        return null;
    }

    /**
     * @param string $jwt
     * @return array
     */
    private static function decodeJWT(string $jwt): array
    {
        $encryptKey = CoreConfigModel::getEncryptKey();
        $jwtKey = new Key($encryptKey, 'HS256');
        $headers = new stdClass();
        $headers->headers = ['HS256'];

        $decoded = JWT::decode($jwt, $jwtKey, $headers);
        return (array)$decoded;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $authMethod
     * @return string|null
     * @throws Exception
     */
    private static function getCurrentSSOIdentity(ServerRequestInterface $request, string $authMethod): ?string
    {
        return match ($authMethod) {
            'sso' => self::getSSOIdentityFromHeaders(),
            'cas' => self::getCASIdentity(),
            'keycloak' => self::getKeycloakIdentity(),
            'azure_saml' => self::getAzureSamlIdentity(),
            default => null,
        };
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private static function getSSOIdentityFromHeaders(): ?string
    {
        $ssoConfiguration = ConfigurationModel::getByPrivilege([
            'privilege' => 'admin_sso',
            'select'    => ['value']
        ]);

        if (empty($ssoConfiguration['value'])) {
            return null;
        }

        $ssoConfiguration = json_decode($ssoConfiguration['value'], true);
        $mapping = array_column($ssoConfiguration['mapping'], 'ssoId', 'maarchId');

        if (empty($mapping['login'])) {
            return null;
        }

        if (!empty($_SERVER['HTTP_' . strtoupper($mapping['login'])])) {
            return $_SERVER['HTTP_' . strtoupper($mapping['login'])];
        } else {
            return $_SERVER[$mapping['login']] ?? null;
        }
    }

    /**
     * @return string|null
     */
    private static function getCASIdentity(): ?string
    {
        if (!class_exists('phpCAS')) {
            return null;
        }

        try {
            try {
                if (phpCAS::isAuthenticated()) {
                    return phpCAS::getUser();
                }
                return null;
            } catch (Exception) {
                $configFile = CoreConfigModel::getJsonLoaded(['path' => 'config/config.json']);
                $maarchUrl = $configFile['config']['maarchUrl'] ?? '';

                $casConfiguration = CoreConfigModel::getXmlLoaded(['path' => 'config/cas_config.xml']);

                if (empty($casConfiguration)) {
                    return null;
                }

                $version = (string)$casConfiguration->CAS_VERSION;
                $hostname = (string)$casConfiguration->WEB_CAS_URL;
                $port = (string)$casConfiguration->WEB_CAS_PORT;
                $uri = (string)$casConfiguration->WEB_CAS_CONTEXT;
                $certificate = (string)$casConfiguration->PATH_CERTIFICATE;

                if (!in_array($version, ['CAS_VERSION_2_0', 'CAS_VERSION_3_0'])) {
                    return null;
                }

                $logConfig = LogsController::getLogConfig();
                $logTypeInfo = LogsController::getLogType('logTechnique');
                $logger = LogsController::initMonologLogger(
                    $logConfig,
                    $logTypeInfo,
                    false,
                    CoreConfigModel::getCustomId()
                );

                phpCAS::setLogger($logger);

                if (!empty($logTypeInfo['errors'])) {
                    return null;
                }

                if ($logTypeInfo['level'] == 'DEBUG') {
                    phpCAS::setVerbose(true);
                }

                phpCAS::client(
                    constant($version),
                    $hostname,
                    (int)$port,
                    $uri,
                    $maarchUrl,
                    $version != 'CAS_VERSION_3_0'
                );

                if (!empty($certificate)) {
                    phpCAS::setCasServerCACert($certificate);
                } else {
                    phpCAS::setNoCasServerValidation();
                }

                phpCAS::setFixedServiceURL(UrlController::getCoreUrl() . '/dist/index.html');
                phpCAS::setNoClearTicketsFromUrl();

                if (phpCAS::isAuthenticated()) {
                    $casId = phpCAS::getUser();

                    $separator = (string)$casConfiguration->ID_SEPARATOR;
                    if (!empty($separator)) {
                        $login = explode($separator, $casId)[0];
                    } else {
                        $login = $casId;
                    }

                    return $login;
                }
            }
        } catch (Exception $e) {
            error_log("CAS Authentication error in middleware: " . $e->getMessage());
            return null;
        }

        return null;
    }

    /**
     * @return string|null
     */
    private static function getKeycloakIdentity(): ?string
    {
        session_start();
        return $_SESSION['keycloak_identity'] ?? null;
    }

    /**
     * @return string|null
     */
    private static function getAzureSamlIdentity(): ?string
    {
        session_start();
        return $_SESSION['azure_saml_identity'] ?? null;
    }
}
