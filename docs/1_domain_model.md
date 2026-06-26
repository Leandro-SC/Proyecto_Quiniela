@startuml

entity leagues {
  * id : BIGINT
  --
  name : VARCHAR
  slug : VARCHAR
}

entity seasons {
  * id : BIGINT
  --
  league_id : BIGINT
  name : VARCHAR
}

entity matchdays {
  * id : BIGINT
  --
  season_id : BIGINT
  league_id : BIGINT
  number : INT
  close_at : DATETIME
}

entity teams {
  * id : BIGINT
  --
  league_id : BIGINT
  name : VARCHAR
}

entity matches {
  * id : BIGINT
  --
  round_id : BIGINT
  home_team_id : BIGINT
  away_team_id : BIGINT
  status : ENUM
}

entity purchase_sessions {
  * id : BIGINT
  --
  session_code : VARCHAR
  user_name : VARCHAR
  phone : VARCHAR
}

entity tickets {
  * id : BIGINT
  --
  ticket_code : VARCHAR
  purchase_session_id : BIGINT
  round_id : BIGINT
  league_id : BIGINT
  total_amount : DECIMAL
  status : ENUM
}

entity ticket_items {
  * id : BIGINT
  --
  ticket_id : BIGINT
  match_id : BIGINT
  selection : CHAR(1)
}

entity promotions {
  * id : BIGINT
  --
  type : ENUM
  value : DECIMAL
}

entity matchday_prizes {
  * id : BIGINT
  --
  round_id : BIGINT
  total_pool_percent : DECIMAL
}

entity ranking_snapshots {
  * id : BIGINT
  --
  round_id : BIGINT
  type : ENUM
}

entity ranking_snapshot_items {
  * id : BIGINT
  --
  snapshot_id : BIGINT
  ticket_id : BIGINT
  position : INT
  points : INT
}

entity admin_users {
  * id : BIGINT
  --
  email : VARCHAR
  password_hash : VARCHAR
  role : ENUM
}

leagues ||--o{ seasons
seasons ||--o{ matchdays
leagues ||--o{ teams
matchdays ||--o{ matches
teams ||--o{ matches
matchdays ||--o{ tickets
tickets ||--o{ ticket_items
purchase_sessions ||--o{ tickets
promotions ||--o{ purchase_sessions
promotions ||--o{ tickets
matchdays ||--|| matchday_prizes
matchdays ||--o{ ranking_snapshots
ranking_snapshots ||--o{ ranking_snapshot_items

@enduml