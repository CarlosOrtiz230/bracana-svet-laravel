// TestVulnerabilities.java

import java.io.*;
import java.net.*;
import java.security.*;
import java.sql.*;
import java.util.*;
import javax.xml.parsers.*;
import org.xml.sax.*;

public class TestVulnerabilities {

    // 1. SQL Injection via string concatenation
    public List<String> getUser(String username) throws SQLException {
        Connection conn = DriverManager.getConnection("jdbc:sqlite:users.db");
        Statement stmt = conn.createStatement();
        String q = "SELECT * FROM users WHERE name = '" + username + "'";
        ResultSet rs = stmt.executeQuery(q);
        // ...
        return Collections.emptyList();
    }

    // 2. Command injection via Runtime.exec
    public void listFiles(String dir) throws IOException {
        Runtime.getRuntime().exec("ls " + dir);
    }

    // 3. Using java.util.Random for security tokens
    public String generateToken() {
        Random rnd = new Random();
        return Long.toHexString(rnd.nextLong());
    }

    // 4. Hardcoded credentials
    private static final String ADMIN_USER = "admin";
    private static final String ADMIN_PASS = "ChangeMe!";

    // 5. Insecure deserialization
    public Object loadObject(byte[] data) throws IOException, ClassNotFoundException {
        ObjectInputStream in = new ObjectInputStream(new ByteArrayInputStream(data));
        return in.readObject();
    }

    // 6. XXE in XML parser
    public void parseXml(String xml) throws Exception {
        DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
        // dbf.setFeature(XMLConstants.FEATURE_SECURE_PROCESSING, true);
        DocumentBuilder db = dbf.newDocumentBuilder();
        db.parse(new InputSource(new StringReader(xml)));
    }

    // 7. Path traversal when reading files
    public String readFile(String filename) throws IOException {
        return new String(Files.readAllBytes(Paths.get("/data/" + filename)));
    }

    // 8. Weak hashing (MD5)
    public String hashPassword(String pw) throws NoSuchAlgorithmException {
        MessageDigest md = MessageDigest.getInstance("MD5");
        byte[] digest = md.digest(pw.getBytes());
        return Base64.getEncoder().encodeToString(digest);
    }

    // 9. Exposing debug info
    public void riskyMethod() {
        try {
            // ...
        } catch (Exception e) {
            e.printStackTrace();  // prints stack trace to client logs
        }
    }

    // 10. Insecure HTTP (no TLS)
    public void sendPassword(String pass, String host) throws IOException {
        Socket socket = new Socket(host, 80);
        PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
        out.println("POST /login HTTP/1.1");
        out.println("Host: " + host);
        out.println("Content-Length: " + pass.length());
        out.println();
        out.print("password=" + pass);
        socket.close();
    }

    public static void main(String[] args) {
        System.out.println("Test file with vulnerabilities");
    }
}

