<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;

    /**
     *
     * Modified Version of Slims Response Emitter.
     *
     * @link https://github.com/slimphp/Slim/blob/4.x/Slim/ResponseEmitter.php
     *
     * Changed method visibility to accommodate Wordpress needs.
     *
     *
     */
    class ResponseEmitter
    {

        /**
         * @var int
         */
        private $responseChunkSize;

        /**
         * @param  int  $responseChunkSize
         */
        public function __construct(int $responseChunkSize = 4096)
        {
            $this->responseChunkSize = $responseChunkSize;
        }

        /**
         * Send the response the client
         *
         * @param  ResponseInterface  $response
         *
         * @return void
         */
        public function emit(ResponseInterface $response) : void
        {

            $isEmpty = $this->isResponseEmpty($response);
            if (headers_sent() === false) {
                $this->emitStatusLine($response);
                $this->emitHeaders($response);
            }

            if ( ! $isEmpty ) {
                $this->emitBody($response);
            }
        }

        /**
         * Emit Response Headers
         *
         * @param  ResponseInterface  $response
         */
        public function emitHeaders(ResponseInterface $response) : void
        {

            if (headers_sent()) {

                return;

            }

            foreach ($response->getHeaders() as $name => $values) {
                $first = strtolower($name) !== 'set-cookie';
                foreach ($values as $value) {
                    $header = sprintf('%s: %s', $name, $value);
                    header($header, $first);
                    $first = false;
                }
            }

            $this->emitStatusLine($response);

        }

        /**
         * Emit Status Line
         *
         * @param  ResponseInterface  $response
         */
        private function emitStatusLine(ResponseInterface $response) : void
        {

            $statusLine = sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );
            header($statusLine, true, $response->getStatusCode());
        }

        /**
         * Emit Body
         *
         * @param  ResponseInterface  $response
         */
        public function emitBody(ResponseInterface $response) : void
        {

            $body = $response->getBody();

            if ($body->isSeekable()) {
                $body->rewind();
            }

            $amountToRead = (int) $response->getHeaderLine('Content-Length');
            if ( ! $amountToRead ) {
                $amountToRead = $body->getSize();
            }

            if ($amountToRead) {

                while ($amountToRead > 0 && ! $body->eof()) {
                    $length = min($this->responseChunkSize, $amountToRead);
                    $data = $body->read($length);
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() !== CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
            else {
                while ( ! $body->eof()) {
                    echo $body->read($this->responseChunkSize);
                    if (connection_status() !== CONNECTION_NORMAL) {
                        break;
                    }
                }
            }


        }

        /**
         * Asserts response body is empty or status code is 204, 205 or 304
         *
         * @param  ResponseInterface  $response
         *
         * @return bool
         */
        private function isResponseEmpty(ResponseInterface $response) : bool
        {

            if (in_array($response->getStatusCode(), [204, 205, 304], true)) {
                return true;
            }
            $stream = $response->getBody();
            $seekable = $stream->isSeekable();
            if ($seekable) {
                $stream->rewind();
            }

            return $seekable ? $stream->read(1) === '' : $stream->eof();
        }



    }