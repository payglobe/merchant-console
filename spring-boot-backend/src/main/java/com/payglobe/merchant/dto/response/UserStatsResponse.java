package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class UserStatsResponse {

    private Long totalUsers;
    private Long activeUsers;
    private Long pendingPasswordChange;
    private Long expiredPasswords;
}
