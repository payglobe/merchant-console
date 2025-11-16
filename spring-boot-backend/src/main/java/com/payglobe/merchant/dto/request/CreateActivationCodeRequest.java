package com.payglobe.merchant.dto.request;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Pattern;
import jakarta.validation.constraints.Size;
import lombok.Data;

@Data
public class CreateActivationCodeRequest {

    @NotBlank(message = "Terminal ID è obbligatorio")
    @Pattern(regexp = "^[A-Za-z0-9]{6,15}$", message = "Terminal ID deve contenere 6-15 caratteri alfanumerici")
    private String terminalId;

    @Size(max = 10, message = "BU non può superare 10 caratteri")
    private String bu;

    @Pattern(regexp = "^(it|en|de|fr|es)$", message = "Lingua non valida")
    private String language = "it";

    @Size(max = 255, message = "Note non possono superare 255 caratteri")
    private String notes;
}
