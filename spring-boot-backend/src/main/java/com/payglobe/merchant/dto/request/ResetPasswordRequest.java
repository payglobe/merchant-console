package com.payglobe.merchant.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class ResetPasswordRequest {

    @NotBlank(message = "Nuova password è obbligatoria")
    @Size(min = 8, message = "Password deve contenere almeno 8 caratteri")
    private String newPassword;
}
