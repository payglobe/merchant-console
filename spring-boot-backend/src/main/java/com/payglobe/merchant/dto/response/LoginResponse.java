package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

/**
 * DTO per risposta login
 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class LoginResponse {

    private String accessToken;
    private String refreshToken;
    private String tokenType = "Bearer";

    // User info
    private Long userId;
    private String email;
    private String bu;
    private String ragioneSociale;
    private Boolean isAdmin;
    private Boolean forcePasswordChange;

}
