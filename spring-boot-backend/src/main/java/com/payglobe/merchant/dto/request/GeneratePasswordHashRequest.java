package com.payglobe.merchant.dto.request;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

@Data
public class GeneratePasswordHashRequest {

    @NotBlank(message = "Password è obbligatoria")
    private String password;
}
