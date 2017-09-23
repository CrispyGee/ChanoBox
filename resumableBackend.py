import time
import BaseHTTPServer
import urlparse


HOST_NAME = 'localhost' # !!!REMEMBER TO CHANGE THIS!!!
PORT_NUMBER = 81 # Maybe set this to 9000.

def extractParameter(parameterKey, request):
    parameter = urlparse.parse_qs(urlparse.urlparse(request.path).query).get(parameterKey, None)
    if not parameter:    
        return None
    if len(parameter) > 0:
        return parameter[0]


class MyHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_HEAD(s):
        s.send_response(200)
        s.send_header('Access-Control-Allow-Origin', '*')
        #s.send_header("Content-type", "text/html")
        s.end_headers()
    def do_OPTIONS(s):
        s.send_response(200)
        s.send_header('Access-Control-Allow-Origin', '*')
        #s.send_header("Content-type", "text/html")
        s.end_headers()
    def do_GET(s):
        """Respond to a GET request."""
        s.send_response(208)
        s.send_header('Access-Control-Allow-Origin', '*')
        #s.send_header("Content-type", "text/html")
        s.end_headers()
    def do_POST(s):
        #content_len = int(s.headers.getheader('content-length', 0))
        #post_body = s.rfile.read(content_len)
        s.send_response(200)
        #s.send_header("Content-type", "text/raw")
        s.send_header('Access-Control-Allow-Origin', '*')
        s.end_headers()
        #s.wfile.write("Request Path %s\n" % s.path)
        #s.wfile.write("Request Parameters" + "\n")
        #p1 = extractParameter("p1", s)
        #if (p1):
        #    s.wfile.write("p1: " + p1 + "\n")
        #p2 = extractParameter("p2", s)
        #if (p2):
        #    s.wfile.write("p2: " + p2 + "\n")
        #s.wfile.write("Request Headers: " + "\n")
        #headers = s.headers
        #s.wfile.write(str(headers))
        #s.wfile.write("Request Body: " + str(post_body) + "\n")

if __name__ == '__main__':
    server_class = BaseHTTPServer.HTTPServer
    httpd = server_class((HOST_NAME, PORT_NUMBER), MyHandler)
    print time.asctime(), "Server Starts - %s:%s" % (HOST_NAME, PORT_NUMBER)
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        pass
    httpd.server_close()
    print time.asctime(), "Server Stops - %s:%s" % (HOST_NAME, PORT_NUMBER)