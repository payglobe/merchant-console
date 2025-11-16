package com.payglobe.merchant.dto.response;

import com.payglobe.merchant.entity.User;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.time.LocalDateTime;
import java.time.temporal.ChronoUnit;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class UserResponse {

    private Long id;
    private String email;
    private String bu;
    private String ragioneSociale;
    private Boolean active;
    private Boolean forcePasswordChange;
    private LocalDateTime passwordLastChanged;
    private LocalDateTime lastLogin;
    private LocalDateTime createdAt;
    private Long passwordAge;  // Giorni
    private Boolean passwordExpired;
    private Boolean isAdmin;

    /**
     * Converti entity in DTO
     */
    public static UserResponse fromEntity(User entity) {
        Long passwordAge = null;
        if (entity.getPasswordLastChanged() != null) {
            passwordAge = ChronoUnit.DAYS.between(entity.getPasswordLastChanged(), LocalDateTime.now());
        }

        return UserResponse.builder()
            .id(entity.getId())
            .email(entity.getEmail())
            .bu(entity.getBu())
            .ragioneSociale(entity.getRagioneSociale())
            .active(entity.getActive())
            .forcePasswordChange(entity.getForcePasswordChange())
            .passwordLastChanged(entity.getPasswordLastChanged())
            .lastLogin(entity.getLastLogin())
            .createdAt(entity.getCreatedAt())
            .passwordAge(passwordAge)
            .passwordExpired(entity.isPasswordExpired())
            .isAdmin(entity.isAdmin())
            .build();
    }
}
