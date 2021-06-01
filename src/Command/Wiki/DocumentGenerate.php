<?php

declare(strict_types=1);

namespace TheFairLib\Command\Wiki;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\Ast;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\HttpServer\Router\RouteCollector;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Annotation\Doc;
use TheFairLib\Contract\FileInterface;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Http\Request\RequestBase;
use Throwable;

class DocumentGenerate extends RequestBase
{

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    protected $printer;

    /**
     * @var array
     */
    private $doc = [];

    /**
     * @Inject()
     * @var FileInterface
     */
    protected $fileService;

    /**
     * @Inject()
     * @var FilesystemFactory
     */
    public $factory;

    /**
     * @Inject
     * @var Yapi
     */
    private $yapiDocService;

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ConfigInterface $config)
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();
        parent::__construct($container, $request, $config);
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws Throwable
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        if (!config('docs.enable', false)) {
            $output->writeln('------------------ docs disable ------------------');
            return;
        }

        $this->documentGenerate();
        $output->writeln('------------------ syncing docs ------------------');
        foreach ($this->doc as $path => $item) {
            $title = $item['name'];
            $method = "POST";
            $desc = $item['desc'];
            $status = "done";
            $responseResult = encode($item['response_result']);
            $reqQuery = [];
            if (!empty($item['params'])) {
                foreach ($item['params'] as $paramName => $paramRule) {
                    $reqQuery[] = [
                        "name" => $paramName,
                        "required" => intval($paramRule['required']),
                        "desc" => $paramRule['rule'],
                    ];
                }
            }

            // 取注释里的第一个 tag 作为 category 名
            $firstTagName = $item['tag'][0] ?? 'default';
            $categoryId = $this->yapiDocService->addCategory(strtolower($firstTagName));
            $result = $this->yapiDocService->addDoc($categoryId, $title, $desc, $item['route'], $status, $method, $reqQuery, [], [], $responseResult);
            $output->writeln("   API: " . $path . " result: " . arrayGet($result, 'errmsg'));
        }
        $output->writeln('------------------ success ------------------');
    }


    /**
     * 测试是否编写文档
     *
     * @throws Throwable
     */
    public function documentGenerate()
    {
        $factory = $this->container->get(DispatcherFactory::class);
        /**
         * @var RouteCollector $router
         */
        $router = $factory->getRouter('http');//获得路由信息

        [$staticRouters, $variableRouters] = $router->getData();
        foreach ($staticRouters as $method => $items) {
            foreach ($items as $route => $handler) {
                $this->analyzeHandler(unCamelize($route), $method, $handler);
            }
        }

        printf("\n--------Test Doc end------------\n\n");
    }

    /**
     * @param string $route
     * @param string $methodType
     * @param Handler $handler
     * @throws Throwable
     */
    protected function analyzeHandler(string $route, string $methodType, Handler $handler)
    {
        [$className, $method] = $this->prepareHandler($handler->callback);
//        rd_debug([$route, $methodType, $handler, $className, $method]);
        $this->docHandler($className, $method, $route, $handler);
        $this->updateYApi();
    }

    /**
     * 测试 class 文档是否完成
     *
     * @param string $className
     * @param string $method
     * @param string $route
     * @param Handler $handler
     * @throws Throwable
     */
    private function docHandler(string $className, string $method, string $route, Handler $handler)
    {
        if (isset($this->doc[$className])) {
            return;
        }
        if ($this->checkWhitelist($route)) {//过滤白单名
            return;
        }
        if ($this->checkBlacklist($method, $route)) {//过滤黑单名
            return;
        }

        $classDoc = AnnotationCollector::getClassAnnotation($className, Doc::class);
        if (!($classDoc instanceof Doc)) {
            throw new ServiceException(sprintf('%s Add the controller documentation', $className));
        }

        /**
         * @var Doc $methodDoc
         */
        $methodDoc = AnnotationCollector::getClassMethodAnnotation($className, $method)[Doc::class] ?? null;
        $pathInfo = $this->explodeRoute($route);
        if (count($pathInfo) !== 3) {
            return;
        }
        [$modules, $controller, $action] = $pathInfo;
        if (!$methodDoc || !($methodDoc instanceof Doc)) {
            throw new ServiceException(sprintf('%s\\% s Add the method documentation', $className, $method));
        }

        foreach (['name'] as $name) {
            if (empty($methodDoc->$name)) {
                throw new ServiceException(sprintf('%s\\%s Add the method documentation %s', $className, $method, $name));
            }
        }

        $routePath = ltrim(bigCamelize($handler->route), '/');
        $requestClassName = sprintf('App\Request\%s', str_replace('/', '\\', $routePath));
        $requestClassPath = sprintf('%s/app/Request/%s.php', BASE_PATH, $routePath);
        if (!file_exists($requestClassPath)) {
            throw new ServiceException(sprintf('CONFIG FILE %s NOT FOUND', $requestClassPath), [
                'router' => $handler->route,
                'class' => $className,
                'method' => $method,
            ]);
        }

        if (empty($methodDoc->tag)) {
            $methodDoc->tag[] = $controller;
        }
        $responseResult = '';
        if ($this->factory->get('local')->has($this->getResponseResultPath($handler))) {
            $responseResult = decode($this->factory->get('local')->read($this->getResponseResultPath($handler)));
        }
        $this->doc[$route] = array_merge([
            'class' => $className,
            'method' => $method,
            'modules' => $modules,
            'controller' => $controller,
            'action' => unCamelize($method),
            'route' => arrayGet($this->getConfig(), 'url_prefix', '') . $route,
            'class_name' => $className,
            'name' => $methodDoc->name,
            'desc' => $methodDoc->desc,
            'params' => $this->getRules($requestClassName),
            'response_result' => $responseResult,
        ], (array)$methodDoc);
    }

    protected function updateYApi()
    {
        $config = $this->getConfig();
        if (!arrayGet($config, 'enable', false)) {
            return;
        }
        $this->fileService->fileLocal(sprintf('doc/%s.json', 'wiki'), encode($this->doc));
    }

    protected function getConfig()
    {
        return config('docs', []);
    }

    /**
     * 验证规则
     *
     * @param string $requestClassName
     * @return array
     */
    protected function getRules(string $requestClassName): array
    {
        $rules = $this->container->get($requestClassName)->rules();
        $params = [];
        if ($rules) {
            foreach ($rules as $name => $rule) {
                switch (gettype($rule)) {
                    case 'string':
                        $params[$name]['rule'] = $rule;
                        break;
                    case 'array':
                        $params[$name]['rule'] = implode('|', $rule);
                        break;
                }
                $params[$name]['required'] = $this->getRequired($rule);//是否必填
            }
        }
        return $params;
    }

    /**
     * 是否必填
     *
     * @param $rule
     * @return bool
     */
    protected function getRequired($rule): bool
    {
        switch (gettype($rule)) {
            case 'string':
                return (bool)(strpos($rule, 'required') !== false);
            case 'array':
                foreach ($rule as $value) {
                    if ('required' === $value) {
                        return true;
                    }
                }
                break;
        }
        return false;
    }

    /**
     * 获得 class 与 method, App\Controller\V2\Test::getTest
     *
     * @param array|string $handler
     * @return array
     * @see \Hyperf\HttpServer\CoreMiddleware::prepareHandler()
     */
    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        return [];
    }


    protected function explodeRoute($route): array
    {
        $pathInfo = explode('/', ltrim(strtolower($route), '/'));
        if (count($pathInfo) !== 3) {
            return [];
        }
        return $pathInfo;
    }

    /**
     * doc result 路径
     *
     * @param Handler $handler
     * @return string
     */
    public function getResponseResultPath(Handler $handler): string
    {
        return sprintf('result%s.json', $handler->route);
    }
}
