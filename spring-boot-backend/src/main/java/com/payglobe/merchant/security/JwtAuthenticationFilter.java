package com.payglobe.merchant.security;

import com.payglobe.merchant.repository.UserRepository;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.security.web.authentication.WebAuthenticationDetailsSource;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;

/**
 * Filtro JWT per autenticazione stateless
 *
 * Intercetta ogni richiesta HTTP, estrae il token JWT dall'header Authorization,
 * lo valida e imposta l'Authentication nel SecurityContext
 */
@Component
@Slf4j
@RequiredArgsConstructor
public class JwtAuthenticationFilter extends OncePerRequestFilter {

    private final JwtTokenProvider jwtTokenProvider;
    private final UserRepository userRepository;

    @Override
    protected void doFilterInternal(
            HttpServletRequest request,
            HttpServletResponse response,
            FilterChain filterChain) throws ServletException, IOException {

        log.debug("JWT Filter - Processing request: {} {}", request.getMethod(), request.getRequestURI());

        try {
            // Estrai JWT dalla richiesta
            String jwt = getJwtFromRequest(request);

            log.debug("JWT Filter - Authorization header present: {}", request.getHeader("Authorization") != null);
            log.debug("JWT Filter - Extracted JWT token: {}", jwt != null ? "present (length=" + jwt.length() + ")" : "null");

            if (StringUtils.hasText(jwt)) {
                boolean isValid = jwtTokenProvider.validateToken(jwt);
                log.debug("JWT Filter - Token validation result: {}", isValid);

                if (isValid) {
                    // Ottieni email dal token
                    String email = jwtTokenProvider.getEmailFromToken(jwt);
                    log.info("JWT Filter - Authenticated user: {}", email);

                    // Carica user dal database
                    UserDetails userDetails = userRepository.findByEmail(email)
                        .orElseThrow(() -> new UsernameNotFoundException("User not found: " + email));

                    // Crea Authentication e impostalo nel SecurityContext
                    UsernamePasswordAuthenticationToken authentication =
                        new UsernamePasswordAuthenticationToken(
                            userDetails.getUsername(),
                            null,
                            userDetails.getAuthorities()
                        );
                    authentication.setDetails(new WebAuthenticationDetailsSource().buildDetails(request));

                    SecurityContextHolder.getContext().setAuthentication(authentication);

                    log.info("JWT authentication successful for user: {} with authorities: {}", email, userDetails.getAuthorities());
                } else {
                    log.warn("JWT Filter - Token validation failed");
                }
            } else {
                log.debug("JWT Filter - No JWT token found in request");
            }
        } catch (Exception ex) {
            log.error("Could not set user authentication in security context", ex);
        }

        filterChain.doFilter(request, response);
    }

    /**
     * Estrae il JWT dall'header Authorization
     *
     * Header format: Authorization: Bearer <token>
     */
    private String getJwtFromRequest(HttpServletRequest request) {
        String bearerToken = request.getHeader("Authorization");

        if (StringUtils.hasText(bearerToken) && bearerToken.startsWith("Bearer ")) {
            return bearerToken.substring(7);
        }

        return null;
    }
}
