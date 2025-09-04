#!/usr/bin/env python3
import http.server
import ssl
import socket
from urllib.parse import urlparse

# Get the actual local IP address
def get_local_ip():
    try:
        # Create a socket to get the local IP
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))  # Connect to Google DNS
        local_ip = s.getsockname()[0]
        s.close()
        return local_ip
    except:
        return "192.168.0.220"  # Fallback

local_ip = get_local_ip()

class CustomHTTPRequestHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        # Add CORS headers for development
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'X-Requested-With')
        super().end_headers()

    def log_message(self, format, *args):
        # Custom logging
        super().log_message(format, *args)

# Create server
server_address = ('', 8443)
httpd = http.server.HTTPServer(server_address, CustomHTTPRequestHandler)

# Create SSL context
ssl_context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
ssl_context.load_cert_chain(certfile='cert.pem', keyfile='key.pem')

# Wrap socket with SSL
httpd.socket = ssl_context.wrap_socket(httpd.socket, server_side=True)

print("🔒 HTTPS Server Started!")
print(f"📱 Local access: https://localhost:8443")
print(f"🌐 Network access: https://{local_ip}:8443")
print("\n⚠️  IMPORTANT: You must accept the security warning in your browser")
print("   Click 'Advanced' → 'Proceed to localhost (unsafe)'")
print("\n📋 For Android tablet:")
print(f"   1. Open Chrome: https://{local_ip}:8443")
print("   2. Accept the security certificate")
print("   3. Allow camera permissions")

try:
    httpd.serve_forever()
except KeyboardInterrupt:
    print("\nServer stopped.")
    httpd.shutdown()
