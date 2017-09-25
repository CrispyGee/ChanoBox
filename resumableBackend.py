import time
import BaseHTTPServer
import urlparse
import os
import cgi
import hashlib

HOST_NAME = 'localhost'
PORT_NUMBER = 8082
UPLOAD_DIR = '/Library/WebServer/Documents/uploader/files/'

def md5(fname):
    hash_md5 = hashlib.md5()
    with open(fname, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()

def extractParameter(parameterKey, request):
    parameter = urlparse.parse_qs(urlparse.urlparse(request.path).query).get(parameterKey, None)
    if not parameter:    
        return None
    if len(parameter) > 0:
        return parameter[0]

def getChunkFilename(resumableIdentifier, resumableFilename, resumableChunkNumber):
    return UPLOAD_DIR + getChunkFilePrefix(resumableIdentifier, resumableFilename) + '-part' + resumableChunkNumber

def getChunkFilePrefix(resumableIdentifier, resumableFilename):
    return "_temp-" + resumableIdentifier + '-' + resumableFilename

def removeChunkFiles(resumableIdentifier, resumableFilename):
    prefix = getChunkFilePrefix(resumableIdentifier, resumableFilename)
    for current_file in os.listdir(UPLOAD_DIR):
        if str(current_file).startswith(prefix):
            file_path = UPLOAD_DIR + current_file
            os.remove(file_path)

def createFileFromChunks(identifier, filename, total_size_client, total_chunks):
    #count total chunk size first
    total_size_server = 0
    temp_total = 0
    prefix = getChunkFilePrefix(identifier, filename)
    for current_file in os.listdir(UPLOAD_DIR):
        if str(current_file).startswith(prefix):
            total_size_server += os.path.getsize(UPLOAD_DIR + current_file)
    print total_size_client
    print total_size_server
    # now check if all chunks are there and iteratively append to assemble actual file
    if total_size_server >= total_size_client:
        filePath = UPLOAD_DIR + filename
        with open(filePath, 'a') as assembledFile:
            for i in xrange(1, total_chunks+1):
                chunkFilename = getChunkFilename(identifier, filename, str(i))
                with open(chunkFilename, 'r') as chunk:
                    chunkData = chunk.read()
                    assembledFile.write(chunkData)
        md5_hash = md5(filePath)
        os.rename(filePath, UPLOAD_DIR + md5_hash + filename)
        removeChunkFiles(identifier, filename)

class MyHandler(BaseHTTPServer.BaseHTTPRequestHandler):
    def do_HEAD(s):
        """Respond to HEAD and OPTIONS with empty 200 because resumable needs it"""
        s.send_response(200)
        s.send_header('Access-Control-Allow-Origin', '*')
        s.end_headers()
    def do_OPTIONS(s):
        """Respond to HEAD and OPTIONS with empty 200 because resumable needs it"""
        s.send_response(200)
        s.send_header('Access-Control-Allow-Origin', '*')
        s.end_headers()
    def do_GET(s):
        """Respond to a GET request. Requests that check if chunk files are present"""
        resumableIdentifier = extractParameter("resumableIdentifier", s)
        resumableFilename = extractParameter("resumableFilename", s)
        resumableChunkNumber = extractParameter("resumableChunkNumber", s)
        if resumableIdentifier and resumableFilename and resumableChunkNumber:
            chunkFilename = getChunkFilename(resumableIdentifier, resumableFilename, resumableChunkNumber)
            if os.path.exists(chunkFilename):
                s.send_response(200)
            else:
                s.send_response(404)
        else: 
            s.send_response(400)
        s.send_header('Access-Control-Allow-Origin', '*')
        s.end_headers()
    def do_POST(s):
        """Respond to a POST request. Requests that save chunks and finally assemble"""
        #length = int(s.headers.getheader('content-length', 0))
        #data = s.rfile.read(int(length))
        resumableIdentifier = extractParameter("resumableIdentifier", s)
        resumableFilename = extractParameter("resumableFilename", s)
        resumableChunkNumber = extractParameter("resumableChunkNumber", s)
        resumableTotalSize = long(extractParameter("resumableTotalSize", s))
        resumableTotalChunks = long(extractParameter("resumableTotalChunks", s))
        if resumableIdentifier and resumableFilename and resumableChunkNumber:
            chunkFilename = getChunkFilename(resumableIdentifier, resumableFilename, resumableChunkNumber)
            ctype, pdict = cgi.parse_header(s.headers['content-type'])
            postvars = cgi.parse_multipart(s.rfile, pdict)
            if os.path.exists(chunkFilename):
                s.send_response(200)
            elif postvars and postvars["file"]:
                current_file = postvars["file"]
                with open(chunkFilename, 'w') as fh:
                    for x in current_file:
                        fh.write(x)
                createFileFromChunks(resumableIdentifier, resumableFilename, resumableTotalSize, resumableTotalChunks)
                s.send_response(200)
            else:
                s.send_response(500)
        else: 
            s.send_response(400)
        s.send_header('Access-Control-Allow-Origin', '*')
        s.end_headers()

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