package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class ActivationCodeStatsResponse {

    private Long total;
    private Long active;
    private Long used;
    private Long expired;
}
