<?php

namespace Fh\QueryBuilder;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Mockery as m;
use Illuminate\Database\MySqlConnection;

abstract class QueryBuilderTestBase extends TestBase {

    protected function assertIsClosure($suspect) {
        $this->assertInternalType('object',$suspect);
        $this->assertInstanceOf('Closure',$suspect);
    }

    /**
     * Creates an Illuminate\Http\Request
     * and returns it.
     *
     * @param  string $strUri  [description]
     * @param  array  $aPost   [description]
     * @param  string $body    [description]
     * @param  array  $files   [description]
     * @param  array  $cookies [description]
     * @return Illuminate\Http\Request
     */
    protected function createRequest(
        $strUri
        ,$aPost = []
        ,$body = ''
        ,$files = []
        ,$cookies = []
    ) {
        $parts = explode('?',$strUri);
        $strQuery = @$parts[1];
        $query = [];
        if(@$parts[1]) parse_str($parts[1],$query);
        $strBaseUri = $parts[0];
        $post = [];
        $attributes = pathinfo($parts[0]);
        $server = [];
        $server['REDIRECT_STATUS'] = '200';
        $server['HTTP_HOST'] = 'jwlocal.wl3.fh.org';
        $server['HTTP_CONNECTION'] = 'keep-alive';
        $server['HTTP_CACHE_CONTROL'] = 'max-age=0';
        $server['HTTP_COOKIE'] = '';
        $server['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $server['HTTP_UPGRADE_INSECURE_REQUESTS'] = '1';
        $server['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/48.0.2564.116 Safari/537.36';
        $server['HTTP_ACCEPT_ENCODING'] = 'gzip, deflate, sdch';
        $server['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.8,el;q=0.6';
        $server['PATH'] = '/usr/bin:/bin:/usr/sbin:/sbin';
        $server['SERVER_SIGNATURE'] = '';
        $server['SERVER_SOFTWARE'] = 'Apache';
        $server['SERVER_NAME'] = 'jwlocal.wl3.fh.org';
        $server['SERVER_ADDR'] = '::1';
        $server['SERVER_PORT'] = '80';
        $server['REMOTE_ADDR'] = '::1';
        $server['DOCUMENT_ROOT'] = '/Users/jwatson/Source/wl3.fh.org/public';
        $server['SERVER_ADMIN'] = 'you@example.com';
        $server['SCRIPT_FILENAME'] = '/Users/jwatson/Source/wl3.fh.org/public/index.php';
        $server['REMOTE_PORT'] = '61688';
        $server['REDIRECT_QUERY_STRING'] = $strQuery;
        $server['REDIRECT_URL'] = $strBaseUri;
        $server['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $server['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $server['REQUEST_METHOD'] = 'GET';
        $server['QUERY_STRING'] = $strQuery;
        $server['REQUEST_URI'] = $strBaseUri . '?' . $strQuery;
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PHP_SELF'] = '/index.php';
        $server['REQUEST_TIME_FLOAT'] = '1456465440.38';
        $server['REQUEST_TIME'] = '1456465440';
        $server['argv'] = [$strQuery];
        $server['argc'] = '1';
        $content = $body;

        // Symfony Request parameters
        // @param array           $query      The GET parameters
        // @param array           $request    The POST parameters
        // @param array           $attributes The request attributes (parameters parsed from the PATH_INFO, ...)
        // @param array           $cookies    The COOKIE parameters
        // @param array           $files      The FILES parameters
        // @param array           $server     The SERVER parameters
        // @param string|resource $content    The raw body data
        $symRequest = new SymfonyRequest(
            $query
            ,$post
            ,$attributes
            ,$cookies
            ,$files
            ,$server
            ,$content
        );
        return Request::createFromBase($symRequest);
    }

    public function getMockConnection() {
        $pdo = m::mock('PDO');
        return new MySqlConnection($pdo,'testdb');
    }

    public function getMockModel($strModelName = 'TestModel') {
        $strModelPath = 'Fh\Data\Mapper\US\\';
        if($strModelName == "TestModel") $strModelPath = "Fh\\QueryBuilder\\";
        $strModelPath = "{$strModelPath}{$strModelName}[getConnection]";
        return m::mock($strModelPath)
                 ->shouldReceive('getConnection')
                 ->andReturn($this->getMockConnection())
                 ->getMock();
    }

    /**
     * Creates a QueryBuilder populated with
     * the information you give it.
     *
     * @param  string $strUri  [description]
     * @param  array  $aPost   [description]
     * @param  string $body    [description]
     * @param  array  $files   [description]
     * @param  array  $cookies [description]
     * @return Illuminate\Http\Request
     */
    public function createQueryBuilder(
        $strUri
        ,$aPost = []
        ,$body = ''
        ,$files = []
        ,$cookies = []
    ) {
        $routeMap = new RouteToModelTestMap();
        $modelMap = $routeMap->getRouteToModelMap();
        $model = $this->getMockModel();
        $request = $this->createRequest($strUri,$aPost,$body,$files,$cookies);
        $queryBuilder = new QueryBuilder($modelMap, $model, $request);
        return $queryBuilder;
    }

    /**
     * Creates a QueryParser populated with
     * the information you give it.
     *
     * @param  string $strUri  [description]
     * @param  array  $aPost   [description]
     * @param  string $body    [description]
     * @param  array  $files   [description]
     * @param  array  $cookies [description]
     * @return Illuminate\Http\Request
     */
    public function createQueryParser(
        $strUri
        ,$aPost = []
        ,$body = ''
        ,$files = []
        ,$cookies = []
    ) {
        $routeMap = new RouteToModelTestMap();
        $modelMap = $routeMap->getRouteToModelMap();
        $request = $this->createRequest($strUri,$aPost,$body,$files,$cookies);
        $queryParser = new QueryParser($modelMap, $request);
        return $queryParser;
    }

    /**
     * Returns a mocked letter object populated with data
     * TODO: This should really go into a fixture
     * @param  integer $LetterId
     * @return Fh\Data\Mapper\US\LetterMapper
     */
    public function getMockLetter($LetterId = 1) {
        $letter = new \Fh\Data\Mapper\US\LetterMapper();
        // Populate $letter object
        $letter->LetterId = $LetterId;
        $letter->StatusId = 2;
        $letter->TemplateId = 3;
        $letter->ChildId = '1000333222';
        $letter->SponsorId = '10101003030';
        $letter->OriginalMessage = 'This is an original message.';
        $letter->EditedMessage = 'This is an edited messgae.';
        $letter->CreatedAt = '2016-02-30 10:24:31';
        return $letter;
    }
}

