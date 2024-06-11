

```mermaid
erDiagram
    USERS {
        INT id PK "Auto Increment"
        VARCHAR(50) username "Not Null"
        VARCHAR(255) password "Not Null"
        BOOLEAN is_admin "Default False"
    }

    MOVIES {
        INT id PK "Auto Increment"
        VARCHAR(255) title "Not Null"
        TEXT description
        DATE release_date
        INT duration
    }

    SHOWS {
        INT id PK "Auto Increment"
        INT movie_id FK "References movies(id)"
        DATETIME show_time
    }

    SEATS {
        INT id PK "Auto Increment"
        INT show_id FK "References shows(id)"
        VARCHAR(5) seat_number
        BOOLEAN is_booked "Default False"
        INT booked_by FK "References users(id)"
    }

    USERS ||--o{ SEATS : "booked_by"
    MOVIES ||--o{ SHOWS : "movie_id"
    SHOWS ||--o{ SEATS : "show_id"

```