package com.payglobe.merchant.util;

import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;

/**
 * Utility per generare hash di password BCrypt compatibili con PHP password_hash()
 *
 * Uso:
 * java -cp target/classes:target/dependency/* com.payglobe.merchant.util.PasswordHashGenerator "your!password@here#123"
 */
public class PasswordHashGenerator {

    public static void main(String[] args) {
        if (args.length == 0) {
            System.out.println("Uso: java PasswordHashGenerator \"password\"");
            System.out.println("Esempio: java PasswordHashGenerator \"Test!123@#\"");
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
        System.out.println("SQL per aggiornare utente:");
        System.out.println("UPDATE users SET password = '" + hash + "' WHERE email = 'user@example.com';");
        System.out.println("");
        System.out.println("Questo hash è compatibile con PHP password_hash() e password_verify()");
        System.out.println("============================================");
    }
}
