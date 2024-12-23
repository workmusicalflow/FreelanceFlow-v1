<?php

namespace App\Models;

/**
 * Représentation d'une mission freelance
 * (service, description, prix, client, etc.)
 */
class Mission
{
    private string $service;
    private string $description;
    private float  $price;
    private string $client;
    private string $clientEmail;
    private string $status;
    private string $invoiceStatus;

    /**
     * Constructeur
     */
    public function __construct(
        string $service,
        string $description,
        float  $price,
        string $client,
        string $clientEmail,
        string $status        = 'En attente',
        string $invoiceStatus = 'non payée'
    ) {
        $this->service       = $service;
        $this->description   = $description;
        $this->price         = $price;
        $this->client        = $client;
        $this->clientEmail   = $clientEmail;
        $this->status        = $status;
        $this->invoiceStatus = $invoiceStatus;
    }

    // Getters
    public function getService(): string
    {
        return $this->service;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getClientEmail(): string
    {
        return $this->clientEmail;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getInvoiceStatus(): string
    {
        return $this->invoiceStatus;
    }

    // Setters (au besoin)
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setInvoiceStatus(string $invoiceStatus): void
    {
        $this->invoiceStatus = $invoiceStatus;
    }
}
