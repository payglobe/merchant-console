package com.payglobe.merchant.dto.request;

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class UpdateUserRequest {

    @NotBlank(message = "Email è obbligatoria")
    @Email(message = "Email non valida")
    private String email;

    @NotBlank(message = "BU è obbligatoria")
    @Size(max = 50, message = "BU non può superare 50 caratteri")
    private String bu;

    @Size(max = 255, message = "Ragione sociale non può superare 255 caratteri")
    private String ragioneSociale;

    private Boolean active;

    private Boolean forcePasswordChange;
}
