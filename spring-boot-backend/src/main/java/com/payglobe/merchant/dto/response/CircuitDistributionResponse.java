package com.payglobe.merchant.dto.response;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.util.Map;

/**
 * DTO per distribuzione circuiti
 */
@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class CircuitDistributionResponse {

    private Map<String, Long> circuits;  // Circuit name -> count

}
