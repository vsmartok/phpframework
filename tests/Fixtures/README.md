# Checking the response along with the headers

Start the built-in PHP server from the WSL terminal:  
`docker exec -it phpframework-php php -S 127.0.0.1:8081 -t tests/Fixtures`

In another terminal, run the following command and check the output:  
`docker exec phpframework-php curl -i http://127.0.0.1:8081/response-emitter.php`

The response must contain:  
status code - 200  
header - X-Test: value #2  
body - test body