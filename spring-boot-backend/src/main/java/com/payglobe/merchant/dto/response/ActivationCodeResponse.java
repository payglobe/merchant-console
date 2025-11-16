package com.payglobe.merchant.dto.response;

import com.payglobe.merchant.entity.ActivationCode;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.time.LocalDateTime;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class ActivationCodeResponse {

    private Integer id;
    private String code;
    private String storeTerminalId;
    private String bu;
    private String status;
    private String language;
    private String notes;
    private String createdBy;
    private LocalDateTime createdAt;
    private LocalDateTime expiresAt;
    private LocalDateTime usedAt;
    private String usedBy;
    private Long daysLeft;
    private Boolean expired;
    private Boolean pending;

    /**
     * Converti entity in DTO
     */
    public static ActivationCodeResponse fromEntity(ActivationCode entity) {
        return ActivationCodeResponse.builder()
            .id(entity.getId())
            .code(entity.getCode())
            .storeTerminalId(entity.getStoreTerminalId())
            .bu(entity.getBu())
            .status(entity.getStatus())
            .language(entity.getLanguage())
            .notes(entity.getNotes())
            .createdBy(entity.getCreatedBy())
            .createdAt(entity.getCreatedAt())
            .expiresAt(entity.getExpiresAt())
            .usedAt(entity.getUsedAt())
            .usedBy(entity.getUsedBy())
            .daysLeft(entity.getDaysLeft())
            .expired(entity.isExpired())
            .pending(entity.isPending())
            .build();
    }
}
