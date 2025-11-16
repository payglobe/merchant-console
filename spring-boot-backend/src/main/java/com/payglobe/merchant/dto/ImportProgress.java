package com.payglobe.merchant.dto;

import lombok.Data;

import java.time.LocalDateTime;

/**
 * DTO per tracciare progresso import BIN table
 */
@Data
public class ImportProgress {

    private String importId;
    private String fileName;
    private String status;  // "processing", "completed", "failed"
    private int totalRecords;
    private int processedRecords;
    private int importedRecords;
    private double progressPercentage;
    private String errorMessage;
    private LocalDateTime startTime;
    private LocalDateTime endTime;

    public ImportProgress(String importId, String fileName) {
        this.importId = importId;
        this.fileName = fileName;
        this.status = "processing";
        this.totalRecords = 0;
        this.processedRecords = 0;
        this.importedRecords = 0;
        this.progressPercentage = 0.0;
        this.startTime = LocalDateTime.now();
    }

    /**
     * Aggiorna progresso
     */
    public void updateProgress(int processedRecords, int totalRecords) {
        this.processedRecords = processedRecords;
        if (totalRecords > 0) {
            this.totalRecords = totalRecords;
            this.progressPercentage = Math.round((processedRecords * 100.0 / totalRecords) * 100.0) / 100.0;
        }
    }

    /**
     * Segna come completato
     */
    public void markCompleted(int importedRecords) {
        this.status = "completed";
        this.importedRecords = importedRecords;
        this.processedRecords = importedRecords;
        this.progressPercentage = 100.0;
        this.endTime = LocalDateTime.now();
    }

    /**
     * Segna come fallito
     */
    public void markFailed(String errorMessage) {
        this.status = "failed";
        this.errorMessage = errorMessage;
        this.endTime = LocalDateTime.now();
    }
}
