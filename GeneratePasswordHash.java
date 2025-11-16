import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;

public class GeneratePasswordHash {
    public static void main(String[] args) {
        if (args.length == 0) {
            System.out.println("Usage: java GeneratePasswordHash <password>");
            System.exit(1);
        }

        String password = args[0];
        BCryptPasswordEncoder encoder = new BCryptPasswordEncoder();
        String hash = encoder.encode(password);

        System.out.println("============================================");
        System.out.println("Password: " + password);
        System.out.println("Hash BCrypt: " + hash);
        System.out.println("============================================");
        System.out.println("");
        System.out.println("SQL:");
        System.out.println("UPDATE users SET password = '" + hash + "' WHERE email = 'marconick@gmail.com';");
        System.out.println("");
    }
}
