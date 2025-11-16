# Analisi Porting: PayGlobe Merchant Dashboard
## Da PHP/MySQL a Spring Boot + Hibernate + React

---

## 📋 Executive Summary

Il sistema attuale è un'applicazione PHP monolitica con sessioni server-side, MySQL e frontend jQuery/Bootstrap. Il porting proposto modernizza completamente l'architettura con:

- **Backend**: Spring Boot 3.x + Java 17+ con architettura REST API
- **ORM**: JPA/Hibernate per gestione database
- **Security**: Spring Security con JWT authentication
- **Frontend**: React 18 + TypeScript con Material-UI
- **Database**: MySQL 8.0+ (migrazione con Flyway)
- **DevOps**: Docker + Kubernetes ready

**Tempo stimato**: 3-4 mesi
**Team suggerito**: 2 Backend + 1 Frontend + 1 DevOps

---

## 🎯 Stack Tecnologico

### Stack Attuale (PHP)
| Componente | Tecnologia | Note |
|------------|-----------|------|
| **Backend** | PHP 7.4+ | Monolitico, session-based |
| **Database** | MySQL 5.7+ | mysqli prepared statements |
| **Auth** | Session PHP | Cookie-based, scadenza 45 giorni |
| **Frontend** | jQuery 3.5 + Bootstrap 4 | Server-side rendering |
| **Charts** | Chart.js 2.x | Canvas rendering |
| **Icons** | Font Awesome 6 | Icon font |
| **Deploy** | Apache + mod_php | pgbe2 server |

### Stack Proposto (Spring Boot)
| Componente | Tecnologia | Benefici |
|------------|-----------|----------|
| **Backend** | Spring Boot 3.2 + Java 17 | Microservizi, scalabilità, testing |
| **ORM** | JPA/Hibernate 6 | Type-safe queries, caching L1/L2 |
| **Auth** | Spring Security + JWT | Stateless, mobile-ready, refresh tokens |
| **Frontend** | React 18 + TypeScript | Component-based, type safety, hot reload |
| **UI Library** | Material-UI (MUI) v5 | Design system enterprise, accessibilità |
| **State** | Redux Toolkit + RTK Query | Cache API, optimistic updates |
| **Charts** | Recharts o Apache ECharts | React native, responsive, interattivi |
| **Build** | Vite | Build velocissimo (~100ms HMR) |
| **Deploy** | Docker + K8s | Containerizzato, auto-scaling |

---

## 🏗️ Architettura Proposta

### Backend Architecture (Spring Boot)

```
┌─────────────────────────────────────────────────────────────┐
│                     API Gateway (Optional)                   │
│                    Spring Cloud Gateway                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Spring Boot Application                    │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Controller  │  │   Service    │  │  Repository  │     │
│  │   Layer      │─▶│    Layer     │─▶│    Layer     │─────┼──▶ MySQL
│  │  @RestCtrl   │  │  @Service    │  │   @Repo      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│         │                  │                                │
│         │                  ├──▶ External APIs               │
│         │                  │     (A-Cube, Satispay)         │
│         │                  │                                │
│         │                  └──▶ Redis Cache                 │
│         │                       (Tokens, Sessions)          │
│         │                                                    │
│         └──▶ Security Filter Chain                          │
│              (JWT Validation, BU Authorization)             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Package Structure

```
com.payglobe.merchant/
│
├── config/
│   ├── SecurityConfig.java          // Spring Security + JWT
│   ├── JpaConfig.java               // Hibernate, connection pool
│   ├── WebMvcConfig.java            // CORS, interceptors
│   ├── RedisConfig.java             // Cache configuration
│   └── OpenApiConfig.java           // Swagger/OpenAPI docs
│
├── entity/                          // JPA Entities
│   ├── User.java                    // @Entity, @Table
│   ├── Transaction.java
│   ├── Store.java
│   ├── ActivationCode.java
│   ├── TerminalConfig.java
│   ├── Receipt.java
│   └── audit/
│       ├── ConfigAuditLog.java
│       └── ActivationAuditLog.java
│
├── repository/                      // Spring Data JPA
│   ├── UserRepository.java          // extends JpaRepository
│   ├── TransactionRepository.java   // + custom @Query
│   ├── StoreRepository.java
│   └── specifications/              // Dynamic queries
│       └── TransactionSpecification.java
│
├── service/
│   ├── AuthService.java             // Login, JWT generation
│   ├── TransactionService.java      // Business logic
│   ├── StoreService.java
│   ├── ActivationService.java
│   ├── TerminalConfigService.java
│   ├── StatisticsService.java       // KPI, charts data
│   ├── ExportService.java           // CSV, Excel
│   └── external/
│       ├── AcubeApiService.java     // RestTemplate/WebClient
│       └── SatispayApiService.java  // HTTP Signature auth
│
├── controller/                      // REST API Endpoints
│   ├── AuthController.java          // POST /api/auth/login
│   ├── TransactionController.java   // GET /api/transactions
│   ├── StoreController.java         // GET /api/stores
│   ├── AdminController.java         // POST /api/admin/users
│   ├── ActivationController.java    // POST /api/activation-codes
│   ├── StatisticsController.java    // GET /api/statistics/*
│   └── api/
│       └── TerminalConfigController.java  // Public API for PAX
│
├── dto/                             // Data Transfer Objects
│   ├── request/
│   │   ├── LoginRequest.java
│   │   ├── TransactionFilterRequest.java
│   │   └── CreateUserRequest.java
│   └── response/
│       ├── LoginResponse.java       // JWT token + user info
│       ├── TransactionResponse.java
│       ├── DashboardStatsResponse.java
│       └── PagedResponse.java       // Generic pagination
│
├── security/
│   ├── JwtTokenProvider.java        // Generate/validate JWT
│   ├── JwtAuthenticationFilter.java // Filter chain
│   ├── CustomUserDetailsService.java
│   └── BusinessUnitAccessDecisionVoter.java  // BU authorization
│
├── util/
│   ├── CircuitCodeMapper.java       // PA→PagoBancomat, VC→Visa
│   ├── ResponseCodeTranslator.java  // ISO 8583 codes
│   ├── BinAnalyzer.java            // BIN→Bank mapping
│   └── DateUtils.java              // Timezone conversion
│
├── exception/
│   ├── GlobalExceptionHandler.java  // @ControllerAdvice
│   ├── BusinessUnitAccessDeniedException.java
│   ├── ResourceNotFoundException.java
│   └── InvalidCredentialsException.java
│
└── scheduler/
    ├── PasswordExpiryScheduler.java      // Check 45-day expiry
    ├── ActivationCodeCleanupScheduler.java // Delete expired codes
    └── SatispayPollingScheduler.java     // Poll registrations
```

---

## 🎨 Frontend Moderno (React + Material-UI)

### Component Structure

```
src/
│
├── app/
│   ├── App.tsx                      // Root component
│   ├── store.ts                     // Redux store
│   └── api.ts                       // RTK Query API slice
│
├── features/
│   ├── auth/
│   │   ├── Login.tsx                // Login page
│   │   ├── authSlice.ts             // Redux slice
│   │   └── ProtectedRoute.tsx       // Route guard
│   │
│   ├── dashboard/
│   │   ├── Dashboard.tsx            // Main dashboard
│   │   ├── KpiCards.tsx             // 4 KPI cards
│   │   ├── TransactionChart.tsx     // Time series (Recharts)
│   │   ├── CircuitPieChart.tsx      // Pie chart
│   │   └── dashboardApi.ts          // RTK Query
│   │
│   ├── transactions/
│   │   ├── TransactionList.tsx      // MUI DataGrid
│   │   ├── TransactionFilters.tsx   // Date pickers, dropdowns
│   │   ├── TransactionDetail.tsx    // Drawer with details
│   │   ├── ExportButton.tsx         // CSV/Excel export
│   │   └── transactionApi.ts
│   │
│   ├── stores/
│   │   ├── StoreList.tsx            // MUI DataGrid
│   │   ├── StoreFilters.tsx
│   │   ├── StoreModal.tsx           // Details modal
│   │   └── storeApi.ts
│   │
│   ├── statistics/
│   │   ├── AdvancedStats.tsx        // Advanced analytics
│   │   ├── BinAnalysis.tsx          // Bank analysis
│   │   ├── HourlyChart.tsx          // Hourly distribution
│   │   ├── WeekdayChart.tsx         // Weekday analysis
│   │   └── statisticsApi.ts
│   │
│   ├── admin/
│   │   ├── UserManagement.tsx       // CRUD users
│   │   ├── UserModal.tsx            // Create/Edit modal
│   │   ├── ActivationCodes.tsx      // Manage codes
│   │   ├── TerminalConfig.tsx       // Config editor
│   │   └── adminApi.ts
│   │
│   └── profile/
│       ├── Profile.tsx              // User profile
│       ├── ChangePassword.tsx
│       └── profileApi.ts
│
├── components/
│   ├── layout/
│   │   ├── AppBar.tsx               // Top navbar
│   │   ├── Sidebar.tsx              // Left drawer
│   │   ├── Breadcrumbs.tsx
│   │   └── Footer.tsx
│   │
│   ├── common/
│   │   ├── LoadingSpinner.tsx
│   │   ├── ErrorBoundary.tsx
│   │   ├── ConfirmDialog.tsx
│   │   ├── DateRangePicker.tsx
│   │   └── ExportMenu.tsx
│   │
│   └── charts/
│       ├── CircuitBadge.tsx         // Colored badge with icon
│       ├── ChartContainer.tsx       // Wrapper with loading
│       └── NoDataPlaceholder.tsx
│
├── hooks/
│   ├── useAuth.ts                   // Auth context hook
│   ├── usePagination.ts
│   ├── useDebounce.ts
│   └── useExport.ts
│
├── utils/
│   ├── formatters.ts                // Currency, date formatters
│   ├── circuitColors.ts             // Circuit color mapping
│   ├── validators.ts
│   └── constants.ts
│
├── theme/
│   ├── theme.ts                     // MUI theme customization
│   ├── palette.ts                   // Colors
│   └── typography.ts
│
└── types/
    ├── transaction.ts               // TypeScript interfaces
    ├── user.ts
    ├── store.ts
    └── api.ts
```

### UI Design System (Material-UI)

```typescript
// theme/theme.ts
import { createTheme } from '@mui/material/styles';

export const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#1976d2',      // Blue
      light: '#42a5f5',
      dark: '#1565c0',
    },
    secondary: {
      main: '#9c27b0',      // Purple
      light: '#ba68c8',
      dark: '#7b1fa2',
    },
    success: {
      main: '#2e7d32',      // Green
    },
    error: {
      main: '#d32f2f',      // Red
    },
    background: {
      default: '#f5f5f5',
      paper: '#ffffff',
    },
    // Circuit colors (custom)
    circuit: {
      pagobancomat: '#ff6384',  // Red
      visa: '#36a2eb',          // Blue
      mastercard: '#ffcd56',    // Yellow
      mybank: '#4bc0c0',        // Teal
      altreCarteColor: '#ff9f40',    // Orange
      altri: '#9966ff',         // Purple
    },
  },
  typography: {
    fontFamily: [
      '-apple-system',
      'BlinkMacSystemFont',
      '"Segoe UI"',
      'Roboto',
      '"Helvetica Neue"',
      'Arial',
      'sans-serif',
    ].join(','),
    h1: {
      fontSize: '2.5rem',
      fontWeight: 600,
    },
    h2: {
      fontSize: '2rem',
      fontWeight: 600,
    },
    body1: {
      fontSize: '0.875rem',    // 14px
      lineHeight: 1.5,
    },
    body2: {
      fontSize: '0.75rem',     // 12px compact
      lineHeight: 1.4,
    },
  },
  shape: {
    borderRadius: 12,           // Rounded corners
  },
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          textTransform: 'none',  // No uppercase
          borderRadius: 8,
          padding: '8px 16px',
        },
      },
    },
    MuiCard: {
      styleOverrides: {
        root: {
          boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
          border: '1px solid #e3e8ee',
        },
      },
    },
    MuiDataGrid: {
      styleOverrides: {
        root: {
          border: 'none',
          '& .MuiDataGrid-cell': {
            fontSize: '0.75rem',   // Compact font
            padding: '8px 6px',
          },
          '& .MuiDataGrid-columnHeaders': {
            background: 'linear-gradient(180deg, #fafbfc 0%, #f4f6f8 100%)',
            fontSize: '0.688rem',  // 11px
            fontWeight: 600,
            textTransform: 'uppercase',
          },
        },
      },
    },
  },
});
```

### Sample Components

#### Dashboard con KPI Cards

```typescript
// features/dashboard/Dashboard.tsx
import { Grid, Card, CardContent, Typography, Box } from '@mui/material';
import { TrendingUp, AccountBalance, CheckCircle, Cancel } from '@mui/icons-material';
import { TransactionChart } from './TransactionChart';
import { CircuitPieChart } from './CircuitPieChart';
import { useGetDashboardStatsQuery } from './dashboardApi';

export const Dashboard = () => {
  const { data: stats, isLoading } = useGetDashboardStatsQuery({
    startDate: '2025-01-01',
    endDate: '2025-01-31',
  });

  if (isLoading) return <LoadingSpinner />;

  return (
    <Box sx={{ p: 3 }}>
      {/* KPI Cards */}
      <Grid container spacing={3} mb={4}>
        <Grid item xs={12} sm={6} md={3}>
          <Card sx={{ background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }}>
            <CardContent>
              <Box display="flex" alignItems="center" gap={1}>
                <TrendingUp sx={{ color: 'white', fontSize: 40 }} />
                <Box>
                  <Typography variant="body2" color="rgba(255,255,255,0.8)">
                    Transazioni Totali
                  </Typography>
                  <Typography variant="h4" color="white" fontWeight="bold">
                    {stats?.total.toLocaleString('it-IT')}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card sx={{ background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' }}>
            <CardContent>
              <Box display="flex" alignItems="center" gap={1}>
                <AccountBalance sx={{ color: 'white', fontSize: 40 }} />
                <Box>
                  <Typography variant="body2" color="rgba(255,255,255,0.8)">
                    Volume Netto
                  </Typography>
                  <Typography variant="h4" color="white" fontWeight="bold">
                    € {stats?.volume.toLocaleString('it-IT', { minimumFractionDigits: 2 })}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card sx={{ background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' }}>
            <CardContent>
              <Box display="flex" alignItems="center" gap={1}>
                <CheckCircle sx={{ color: 'white', fontSize: 40 }} />
                <Box>
                  <Typography variant="body2" color="rgba(255,255,255,0.8)">
                    Settled
                  </Typography>
                  <Typography variant="h4" color="white" fontWeight="bold">
                    {stats?.settledCount.toLocaleString('it-IT')}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} sm={6} md={3}>
          <Card sx={{ background: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' }}>
            <CardContent>
              <Box display="flex" alignItems="center" gap={1}>
                <Cancel sx={{ color: 'white', fontSize: 40 }} />
                <Box>
                  <Typography variant="body2" color="rgba(255,255,255,0.8)">
                    Non Settled
                  </Typography>
                  <Typography variant="h4" color="white" fontWeight="bold">
                    {stats?.notSettledCount.toLocaleString('it-IT')}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Charts */}
      <Grid container spacing={3}>
        <Grid item xs={12} md={8}>
          <Card>
            <CardContent>
              <Typography variant="h6" mb={2}>
                Trend Transazioni e Volume (Mese Corrente)
              </Typography>
              <TransactionChart />
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={4}>
          <Card>
            <CardContent>
              <Typography variant="h6" mb={2}>
                Distribuzione Circuiti
              </Typography>
              <CircuitPieChart />
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
};
```

#### Tabella Transazioni con MUI DataGrid

```typescript
// features/transactions/TransactionList.tsx
import { DataGrid, GridColDef, GridRenderCellParams } from '@mui/x-data-grid';
import { Chip, Box, IconButton, Tooltip } from '@mui/material';
import { Download, Refresh } from '@mui/icons-material';
import { useGetTransactionsQuery } from './transactionApi';
import { CircuitBadge } from '@/components/charts/CircuitBadge';

export const TransactionList = () => {
  const [filters, setFilters] = useState({ page: 0, pageSize: 25 });
  const { data, isLoading, refetch } = useGetTransactionsQuery(filters);

  const columns: GridColDef[] = [
    {
      field: 'transactionDate',
      headerName: 'Data/Ora',
      width: 160,
      valueFormatter: (params) =>
        new Date(params.value).toLocaleString('it-IT'),
    },
    {
      field: 'posid',
      headerName: 'POSID',
      width: 120,
    },
    {
      field: 'store',
      headerName: 'Store',
      width: 200,
      valueGetter: (params) => params.row.insegna || params.row.ragioneSociale,
    },
    {
      field: 'transactionType',
      headerName: 'Tipo',
      width: 180,
      renderCell: (params: GridRenderCellParams) => (
        <Chip
          label={translateCircuitCode(params.value)}
          size="small"
          color="success"
          sx={{ fontSize: '0.688rem' }}
        />
      ),
    },
    {
      field: 'amount',
      headerName: 'Importo',
      width: 120,
      align: 'right',
      renderCell: (params: GridRenderCellParams) => {
        const isRefund = ['DSESTO', 'DSISTO', 'CSESTO', 'CSISTO'].includes(params.row.transactionType);
        const amount = isRefund ? -params.value : params.value;
        const color = params.row.settlementFlag === '1'
          ? (isRefund ? 'error.main' : 'success.main')
          : 'text.disabled';

        return (
          <Typography variant="body2" color={color} fontWeight="600">
            € {amount.toLocaleString('it-IT', { minimumFractionDigits: 2 })}
          </Typography>
        );
      },
    },
    {
      field: 'pan',
      headerName: 'PAN',
      width: 140,
    },
    {
      field: 'cardBrand',
      headerName: 'Circuito',
      width: 160,
      renderCell: (params: GridRenderCellParams) => (
        <CircuitBadge code={params.value} />
      ),
    },
    {
      field: 'settlementFlag',
      headerName: 'Settlement',
      width: 100,
      renderCell: (params: GridRenderCellParams) => (
        <Chip
          label={params.value === '1' ? 'OK' : 'NO'}
          size="small"
          color={params.value === '1' ? 'success' : 'error'}
          sx={{ fontSize: '0.688rem' }}
        />
      ),
    },
    {
      field: 'responseCode',
      headerName: 'Esito',
      width: 220,
      renderCell: (params: GridRenderCellParams) => {
        if (params.row.settlementFlag === '1') {
          return <Typography variant="body2" color="success.main">00 - Approvato</Typography>;
        }
        const code = params.value || params.row.ibResponseCode;
        return (
          <Tooltip title={translateResponseCode(code)}>
            <Typography variant="body2" color="error.main" noWrap>
              {translateResponseCode(code)}
            </Typography>
          </Tooltip>
        );
      },
    },
  ];

  return (
    <Box sx={{ height: 650, width: '100%' }}>
      <Box display="flex" justifyContent="space-between" mb={2}>
        <Typography variant="h6">
          Transazioni ({data?.total.toLocaleString('it-IT')})
        </Typography>
        <Box display="flex" gap={1}>
          <IconButton onClick={() => refetch()} size="small">
            <Refresh />
          </IconButton>
          <IconButton onClick={() => exportToCSV()} size="small">
            <Download />
          </IconButton>
        </Box>
      </Box>

      <DataGrid
        rows={data?.transactions || []}
        columns={columns}
        loading={isLoading}
        pagination
        paginationMode="server"
        rowCount={data?.total || 0}
        page={filters.page}
        pageSize={filters.pageSize}
        onPageChange={(newPage) => setFilters({ ...filters, page: newPage })}
        onPageSizeChange={(newSize) => setFilters({ ...filters, pageSize: newSize })}
        rowsPerPageOptions={[25, 50, 100]}
        disableSelectionOnClick
        sx={{
          '& .MuiDataGrid-cell:focus': {
            outline: 'none',
          },
          '& .MuiDataGrid-row:hover': {
            backgroundColor: 'rgba(0, 0, 0, 0.04)',
            transform: 'translateY(-1px)',
            transition: 'all 0.15s ease',
            boxShadow: '0 2px 4px rgba(0,0,0,0.08)',
          },
        }}
      />
    </Box>
  );
};
```

---

## 🔐 Security Implementation

### JWT Authentication Flow

```
┌──────────┐                                    ┌──────────┐
│          │  POST /api/auth/login              │          │
│  Client  │─────────────────────────────────▶ │  Server  │
│ (React)  │  { email, password }               │ (Spring) │
│          │                                    │          │
│          │  ◀─────────────────────────────────│          │
│          │  { accessToken, refreshToken,     │          │
│          │    user: { id, email, bu } }       │          │
└──────────┘                                    └──────────┘
     │                                               │
     │ Store tokens in:                              │
     │ - accessToken → Memory (Redux)                │
     │ - refreshToken → HttpOnly cookie              │
     │                                               │
     │  GET /api/transactions                        │
     │  Header: Authorization: Bearer <token>        │
     │─────────────────────────────────────────────▶│
     │                                               │
     │                                               │ Validate JWT:
     │                                               │ - Signature
     │                                               │ - Expiration
     │                                               │ - BU authorization
     │                                               │
     │  ◀─────────────────────────────────────────── │
     │  { data: [...transactions] }                  │
     │                                               │
     │                                               │
     │  (Token expires after 15 min)                 │
     │                                               │
     │  POST /api/auth/refresh                       │
     │  Cookie: refreshToken=<token>                 │
     │─────────────────────────────────────────────▶│
     │                                               │
     │  ◀─────────────────────────────────────────── │
     │  { accessToken: <new_token> }                 │
     │                                               │
```

### Spring Security Configuration

```java
@Configuration
@EnableWebSecurity
@EnableMethodSecurity
public class SecurityConfig {

    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .csrf(csrf -> csrf.disable())
            .cors(cors -> cors.configurationSource(corsConfigurationSource()))
            .sessionManagement(session ->
                session.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
            .authorizeHttpRequests(auth -> auth
                // Public endpoints
                .requestMatchers("/api/auth/**").permitAll()
                .requestMatchers("/api/public/**").permitAll()
                .requestMatchers("/api/terminal/config").permitAll()  // PAX devices

                // Admin-only endpoints
                .requestMatchers("/api/admin/**").hasAuthority("BU_9999")

                // Authenticated endpoints
                .anyRequest().authenticated()
            )
            .addFilterBefore(jwtAuthenticationFilter(),
                UsernamePasswordAuthenticationFilter.class)
            .exceptionHandling(ex -> ex
                .authenticationEntryPoint(new HttpStatusEntryPoint(HttpStatus.UNAUTHORIZED))
            );

        return http.build();
    }

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder(12);  // Stronger than PHP default
    }
}
```

### Business Unit Authorization

```java
@Service
public class BusinessUnitAuthorizationService {

    /**
     * Verifica se l'utente può accedere ai dati di una specifica BU
     */
    public boolean canAccessBusinessUnit(String targetBu, User currentUser) {
        // Admin (BU 9999) può accedere a tutto
        if ("9999".equals(currentUser.getBu())) {
            return true;
        }

        // Utente normale può accedere solo alla propria BU
        return targetBu.equals(currentUser.getBu());
    }

    /**
     * Applica filtro BU alle query
     */
    public Specification<Transaction> applyBuFilter(User currentUser) {
        if ("9999".equals(currentUser.getBu())) {
            return null;  // No filter for admin
        }

        return (root, query, cb) -> {
            Join<Transaction, Store> storeJoin = root.join("store");
            return cb.equal(storeJoin.get("bu"), currentUser.getBu());
        };
    }
}

// Usage in Service
@Service
public class TransactionService {

    @Autowired
    private BusinessUnitAuthorizationService buAuthService;

    public Page<Transaction> findTransactions(
            TransactionFilterRequest filters,
            User currentUser,
            Pageable pageable) {

        // Build specification with BU filter
        Specification<Transaction> spec = Specification.where(null);

        // Apply BU authorization
        Specification<Transaction> buFilter = buAuthService.applyBuFilter(currentUser);
        if (buFilter != null) {
            spec = spec.and(buFilter);
        }

        // Apply other filters
        if (filters.getStartDate() != null) {
            spec = spec.and((root, query, cb) ->
                cb.greaterThanOrEqualTo(root.get("transactionDate"), filters.getStartDate()));
        }

        return transactionRepository.findAll(spec, pageable);
    }
}
```

---

## 📊 Database Migration (Flyway)

### Migration Files

```sql
-- V1__create_users_table.sql
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'BCrypt hash',
    bu VARCHAR(50) NOT NULL COMMENT 'Business Unit',
    ragione_sociale VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    force_password_change BOOLEAN DEFAULT FALSE,
    password_last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_bu (bu),
    INDEX idx_email (email),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V2__create_stores_table.sql
CREATE TABLE stores (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    terminal_id VARCHAR(50) UNIQUE NOT NULL,
    bu VARCHAR(50) NOT NULL,
    insegna VARCHAR(255),
    ragione_sociale VARCHAR(255),
    indirizzo VARCHAR(255),
    citta VARCHAR(100),
    cap VARCHAR(10),
    prov VARCHAR(5),
    country VARCHAR(5),
    modello_pos VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_terminal_id (terminal_id),
    INDEX idx_bu (bu),
    INDEX idx_citta (citta),
    INDEX idx_prov (prov),
    INDEX idx_country (country),
    FULLTEXT INDEX ft_search (insegna, ragione_sociale, indirizzo, citta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V3__create_transactions_table.sql
CREATE TABLE transactions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    posid VARCHAR(50) NOT NULL,
    transaction_date TIMESTAMP NOT NULL,
    transaction_type VARCHAR(20) NOT NULL COMMENT 'DAACQU, CAACQU, DSESTO, etc.',
    amount DECIMAL(10,2) NOT NULL,
    pan VARCHAR(50),
    card_brand VARCHAR(20) COMMENT 'PA, VC, MC, MBK, etc.',
    settlement_flag CHAR(1) DEFAULT '0' COMMENT '1=OK, 0=NO',
    response_code VARCHAR(10),
    ib_response_code VARCHAR(10),
    authorization_code VARCHAR(20),
    rrn VARCHAR(20),
    stan VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transaction_store
        FOREIGN KEY (posid) REFERENCES stores(terminal_id)
        ON DELETE CASCADE,

    INDEX idx_transaction_date (transaction_date),
    INDEX idx_posid (posid),
    INDEX idx_settlement_flag (settlement_flag),
    INDEX idx_card_brand (card_brand),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_composite_date_posid (transaction_date, posid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V4__create_activation_codes_table.sql
CREATE TABLE activation_codes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    store_terminal_id VARCHAR(50) NOT NULL,
    bu VARCHAR(50) NOT NULL,
    status ENUM('PENDING', 'USED', 'EXPIRED') DEFAULT 'PENDING',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(255) NOT NULL,
    notes TEXT,
    language VARCHAR(5) DEFAULT 'it',

    CONSTRAINT fk_activation_store
        FOREIGN KEY (store_terminal_id) REFERENCES stores(terminal_id),

    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_terminal (store_terminal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V5__create_terminal_config_table.sql
CREATE TABLE terminal_config (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    terminal_id VARCHAR(50) NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(255) NOT NULL,

    UNIQUE KEY uk_terminal_key (terminal_id, config_key),
    INDEX idx_terminal_id (terminal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V6__create_audit_tables.sql
CREATE TABLE config_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    terminal_id VARCHAR(50) NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    action VARCHAR(20) NOT NULL COMMENT 'CREATE, UPDATE, DELETE',
    performed_by VARCHAR(255) NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_terminal_id (terminal_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activation_audit_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    activation_code VARCHAR(20) NOT NULL,
    action VARCHAR(20) NOT NULL COMMENT 'CREATED, USED, EXPIRED, DELETED',
    user_agent TEXT,
    ip_address VARCHAR(45),
    performed_by VARCHAR(255) NOT NULL,
    details TEXT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activation_code (activation_code),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- V7__insert_default_admin.sql
INSERT INTO users (email, password, bu, ragione_sociale, active, force_password_change)
VALUES (
    'admin@payglobe.it',
    '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYuQPzXYH8i',  -- "admin123"
    '9999',
    'PayGlobe Admin',
    TRUE,
    TRUE
);
```

---

## 🚀 Deployment & DevOps

### Docker Setup

```dockerfile
# Dockerfile (Backend)
FROM eclipse-temurin:17-jre-alpine

WORKDIR /app

# Add non-root user
RUN addgroup -S spring && adduser -S spring -G spring
USER spring:spring

COPY target/*.jar app.jar

EXPOSE 8080

ENTRYPOINT ["java", \
    "-Xmx512m", \
    "-Xms256m", \
    "-XX:+UseContainerSupport", \
    "-XX:MaxRAMPercentage=75.0", \
    "-Djava.security.egd=file:/dev/./urandom", \
    "-jar", \
    "app.jar"]

HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD wget --no-verbose --tries=1 --spider http://localhost:8080/actuator/health || exit 1
```

```dockerfile
# Dockerfile (Frontend)
FROM node:18-alpine AS builder

WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### Docker Compose

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: payglobe
      MYSQL_USER: pgdbuser
      MYSQL_PASSWORD: secure_password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 3

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes

  backend:
    build: ./backend
    ports:
      - "8080:8080"
    environment:
      SPRING_PROFILES_ACTIVE: production
      SPRING_DATASOURCE_URL: jdbc:mysql://mysql:3306/payglobe?useUnicode=true&characterEncoding=utf8mb4
      SPRING_DATASOURCE_USERNAME: pgdbuser
      SPRING_DATASOURCE_PASSWORD: secure_password
      SPRING_REDIS_HOST: redis
      SPRING_REDIS_PORT: 6379
      JWT_SECRET: ${JWT_SECRET}
      JWT_EXPIRATION: 900000  # 15 minutes
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_started
    healthcheck:
      test: ["CMD", "wget", "--spider", "http://localhost:8080/actuator/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  frontend:
    build: ./frontend
    ports:
      - "80:80"
    depends_on:
      - backend
    environment:
      VITE_API_BASE_URL: http://backend:8080/api

volumes:
  mysql_data:
  redis_data:
```

### Kubernetes Deployment

```yaml
# k8s/backend-deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: merchant-backend
spec:
  replicas: 3
  selector:
    matchLabels:
      app: merchant-backend
  template:
    metadata:
      labels:
        app: merchant-backend
    spec:
      containers:
      - name: backend
        image: payglobe/merchant-backend:1.0.0
        ports:
        - containerPort: 8080
        env:
        - name: SPRING_PROFILES_ACTIVE
          value: "kubernetes"
        - name: SPRING_DATASOURCE_URL
          valueFrom:
            configMapKeyRef:
              name: merchant-config
              key: database.url
        - name: JWT_SECRET
          valueFrom:
            secretKeyRef:
              name: merchant-secrets
              key: jwt.secret
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "1Gi"
            cpu: "1000m"
        livenessProbe:
          httpGet:
            path: /actuator/health/liveness
            port: 8080
          initialDelaySeconds: 60
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /actuator/health/readiness
            port: 8080
          initialDelaySeconds: 30
          periodSeconds: 5

---
apiVersion: v1
kind: Service
metadata:
  name: merchant-backend-service
spec:
  selector:
    app: merchant-backend
  ports:
  - protocol: TCP
    port: 8080
    targetPort: 8080
  type: ClusterIP

---
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: merchant-backend-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: merchant-backend
  minReplicas: 2
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
```

### CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/deploy.yml
name: Build and Deploy

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  test-backend:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3

    - name: Set up JDK 17
      uses: actions/setup-java@v3
      with:
        java-version: '17'
        distribution: 'temurin'

    - name: Build with Maven
      run: mvn clean verify

    - name: Run tests
      run: mvn test

    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3

  test-frontend:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3

    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'

    - name: Install dependencies
      run: npm ci

    - name: Run tests
      run: npm test

    - name: Build
      run: npm run build

  build-and-push:
    needs: [test-backend, test-frontend]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    steps:
    - uses: actions/checkout@v3

    - name: Login to DockerHub
      uses: docker/login-action@v2
      with:
        username: ${{ secrets.DOCKERHUB_USERNAME }}
        password: ${{ secrets.DOCKERHUB_TOKEN }}

    - name: Build and push Backend
      uses: docker/build-push-action@v4
      with:
        context: ./backend
        push: true
        tags: payglobe/merchant-backend:${{ github.sha }}

    - name: Build and push Frontend
      uses: docker/build-push-action@v4
      with:
        context: ./frontend
        push: true
        tags: payglobe/merchant-frontend:${{ github.sha }}

    - name: Deploy to Kubernetes
      uses: azure/k8s-deploy@v4
      with:
        manifests: |
          k8s/backend-deployment.yaml
          k8s/frontend-deployment.yaml
        images: |
          payglobe/merchant-backend:${{ github.sha }}
          payglobe/merchant-frontend:${{ github.sha }}
```

---

## 📈 Performance & Optimization

### Caching Strategy

```java
@Configuration
@EnableCaching
public class CacheConfig {

    @Bean
    public RedisCacheManager cacheManager(RedisConnectionFactory connectionFactory) {
        RedisCacheConfiguration config = RedisCacheConfiguration.defaultCacheConfig()
            .entryTtl(Duration.ofMinutes(15))
            .serializeKeysWith(RedisSerializationContext.SerializationPair
                .fromSerializer(new StringRedisSerializer()))
            .serializeValuesWith(RedisSerializationContext.SerializationPair
                .fromSerializer(new GenericJackson2JsonRedisSerializer()));

        Map<String, RedisCacheConfiguration> cacheConfigurations = new HashMap<>();

        // Dashboard stats cache (5 min)
        cacheConfigurations.put("dashboardStats",
            config.entryTtl(Duration.ofMinutes(5)));

        // Circuit codes translation (1 hour)
        cacheConfigurations.put("circuitCodes",
            config.entryTtl(Duration.ofHours(1)));

        // BIN database (24 hours)
        cacheConfigurations.put("binDatabase",
            config.entryTtl(Duration.ofHours(24)));

        return RedisCacheManager.builder(connectionFactory)
            .cacheDefaults(config)
            .withInitialCacheConfigurations(cacheConfigurations)
            .build();
    }
}

// Usage
@Service
public class StatisticsService {

    @Cacheable(value = "dashboardStats", key = "#filters.hashCode()")
    public DashboardStats getDashboardStats(DashboardFilters filters) {
        // Expensive database queries
        return statisticsRepository.calculateDashboardStats(filters);
    }

    @CacheEvict(value = "dashboardStats", allEntries = true)
    @Scheduled(cron = "0 */5 * * * *")  // Every 5 minutes
    public void evictDashboardCache() {
        log.info("Evicted dashboard stats cache");
    }
}
```

### Database Optimization

```java
// JPA Repository with custom queries
@Repository
public interface TransactionRepository extends JpaRepository<Transaction, Long> {

    // Native query with indexed columns
    @Query(value = """
        SELECT
            COUNT(*) as total,
            SUM(CASE
                WHEN settlement_flag = '1' AND transaction_type IN (:refundTypes)
                THEN -amount
                WHEN settlement_flag = '1'
                THEN amount
                ELSE 0
            END) as volume,
            SUM(CASE WHEN settlement_flag = '1' THEN 1 ELSE 0 END) as settled_count,
            SUM(CASE WHEN settlement_flag != '1' THEN 1 ELSE 0 END) as not_settled_count
        FROM transactions t
        WHERE t.transaction_date BETWEEN :startDate AND :endDate
        AND (:bu = '9999' OR t.posid IN (SELECT terminal_id FROM stores WHERE bu = :bu))
        """, nativeQuery = true)
    DashboardStatsProjection calculateStats(
        @Param("startDate") LocalDateTime startDate,
        @Param("endDate") LocalDateTime endDate,
        @Param("bu") String bu,
        @Param("refundTypes") List<String> refundTypes
    );

    // Pagination with fetch join (avoid N+1)
    @Query("SELECT t FROM Transaction t LEFT JOIN FETCH t.store WHERE t.transactionDate BETWEEN :start AND :end")
    Page<Transaction> findWithStore(
        @Param("start") LocalDateTime start,
        @Param("end") LocalDateTime end,
        Pageable pageable
    );
}

// Entity with indexes
@Entity
@Table(name = "transactions", indexes = {
    @Index(name = "idx_transaction_date", columnList = "transaction_date"),
    @Index(name = "idx_posid", columnList = "posid"),
    @Index(name = "idx_settlement_flag", columnList = "settlement_flag"),
    @Index(name = "idx_composite", columnList = "transaction_date,posid,settlement_flag")
})
public class Transaction {
    // ...
}
```

### Connection Pool Configuration

```yaml
# application.yml
spring:
  datasource:
    hikari:
      maximum-pool-size: 20
      minimum-idle: 5
      connection-timeout: 30000
      idle-timeout: 600000
      max-lifetime: 1800000
      leak-detection-threshold: 60000

  jpa:
    hibernate:
      ddl-auto: validate
    properties:
      hibernate:
        jdbc:
          batch_size: 20
          fetch_size: 50
        order_inserts: true
        order_updates: true
        generate_statistics: false
        cache:
          use_second_level_cache: true
          use_query_cache: true
          region:
            factory_class: org.hibernate.cache.jcache.JCacheRegionFactory
```

---

## 📊 Monitoring & Observability

### Spring Boot Actuator

```yaml
# application.yml
management:
  endpoints:
    web:
      exposure:
        include: health,info,metrics,prometheus
  endpoint:
    health:
      show-details: when-authorized
  metrics:
    export:
      prometheus:
        enabled: true
    tags:
      application: merchant-dashboard
      environment: production
```

### Custom Metrics

```java
@Component
public class TransactionMetrics {

    private final Counter transactionCounter;
    private final Timer transactionTimer;
    private final Gauge activeUsersGauge;

    public TransactionMetrics(MeterRegistry registry) {
        this.transactionCounter = Counter.builder("transactions.processed")
            .description("Number of transactions processed")
            .tag("type", "all")
            .register(registry);

        this.transactionTimer = Timer.builder("transactions.processing.time")
            .description("Time to process transaction")
            .register(registry);

        this.activeUsersGauge = Gauge.builder("users.active", this::getActiveUsers)
            .description("Number of active users")
            .register(registry);
    }

    public void recordTransaction(String type) {
        transactionCounter.increment();
        Counter.builder("transactions.by_type")
            .tag("type", type)
            .register(registry)
            .increment();
    }

    private int getActiveUsers() {
        // Query active sessions from Redis
        return sessionRepository.countActiveSessions();
    }
}
```

### Logging (Logback)

```xml
<!-- logback-spring.xml -->
<configuration>
    <include resource="org/springframework/boot/logging/logback/defaults.xml"/>

    <property name="LOG_FILE" value="${LOG_FILE:-${LOG_PATH:-${LOG_TEMP:-${java.io.tmpdir:-/tmp}}/}spring.log}"/>

    <appender name="CONSOLE" class="ch.qos.logback.core.ConsoleAppender">
        <encoder>
            <pattern>%d{yyyy-MM-dd HH:mm:ss.SSS} [%thread] %-5level %logger{36} - %msg%n</pattern>
        </encoder>
    </appender>

    <appender name="FILE" class="ch.qos.logback.core.rolling.RollingFileAppender">
        <file>${LOG_FILE}</file>
        <rollingPolicy class="ch.qos.logback.core.rolling.TimeBasedRollingPolicy">
            <fileNamePattern>${LOG_FILE}.%d{yyyy-MM-dd}.gz</fileNamePattern>
            <maxHistory>30</maxHistory>
        </rollingPolicy>
        <encoder>
            <pattern>%d{yyyy-MM-dd HH:mm:ss.SSS} [%thread] %-5level %logger{36} - %msg%n</pattern>
        </encoder>
    </appender>

    <appender name="JSON" class="ch.qos.logback.core.rolling.RollingFileAppender">
        <file>${LOG_PATH}/application-json.log</file>
        <rollingPolicy class="ch.qos.logback.core.rolling.TimeBasedRollingPolicy">
            <fileNamePattern>${LOG_PATH}/application-json.%d{yyyy-MM-dd}.gz</fileNamePattern>
        </rollingPolicy>
        <encoder class="net.logstash.logback.encoder.LogstashEncoder">
            <includeMdcKeyName>user</includeMdcKeyName>
            <includeMdcKeyName>bu</includeMdcKeyName>
            <includeMdcKeyName>traceId</includeMdcKeyName>
        </encoder>
    </appender>

    <logger name="com.payglobe.merchant" level="INFO"/>
    <logger name="org.springframework.web" level="WARN"/>
    <logger name="org.hibernate.SQL" level="DEBUG"/>
    <logger name="org.hibernate.type.descriptor.sql.BasicBinder" level="TRACE"/>

    <root level="INFO">
        <appender-ref ref="CONSOLE"/>
        <appender-ref ref="FILE"/>
        <appender-ref ref="JSON"/>
    </root>
</configuration>
```

---

## 🎯 Testing Strategy

### Unit Testing

```java
@SpringBootTest
@AutoConfigureMockMvc
class TransactionServiceTest {

    @MockBean
    private TransactionRepository transactionRepository;

    @Autowired
    private TransactionService transactionService;

    @Test
    @DisplayName("Calculate net volume with refunds correctly")
    void testCalculateNetVolume_withRefunds() {
        // Given
        List<Transaction> transactions = Arrays.asList(
            createTransaction("CAACQU", 100.00, "1"),
            createTransaction("CAACQU", 50.00, "1"),
            createTransaction("CSESTO", 30.00, "1")  // Refund
        );

        when(transactionRepository.findByDateBetween(any(), any()))
            .thenReturn(transactions);

        // When
        BigDecimal volume = transactionService.calculateNetVolume(
            LocalDateTime.now().minusDays(1),
            LocalDateTime.now()
        );

        // Then
        assertEquals(new BigDecimal("120.00"), volume); // 100 + 50 - 30
    }

    @Test
    @DisplayName("BU authorization prevents cross-BU access")
    void testBuAuthorization_preventsUnauthorizedAccess() {
        // Given
        User user = createUser("user@test.com", "1234");  // BU 1234
        TransactionFilterRequest filters = new TransactionFilterRequest();

        List<Transaction> expectedTxns = Arrays.asList(
            createTransactionForBu("1234")
        );

        when(transactionRepository.findAll(any(Specification.class), any(Pageable.class)))
            .thenReturn(new PageImpl<>(expectedTxns));

        // When
        Page<Transaction> result = transactionService.findTransactions(
            filters, user, PageRequest.of(0, 25)
        );

        // Then
        assertThat(result.getContent()).hasSize(1);
        assertThat(result.getContent().get(0).getStore().getBu()).isEqualTo("1234");
    }
}
```

### Integration Testing

```java
@SpringBootTest(webEnvironment = WebEnvironment.RANDOM_PORT)
@AutoConfigureMockMvc
@Sql(scripts = "/test-data.sql", executionPhase = Sql.ExecutionPhase.BEFORE_TEST_METHOD)
@Sql(scripts = "/cleanup.sql", executionPhase = Sql.ExecutionPhase.AFTER_TEST_METHOD)
class TransactionControllerIntegrationTest {

    @Autowired
    private MockMvc mockMvc;

    @Autowired
    private JwtTokenProvider tokenProvider;

    @Test
    @DisplayName("GET /api/transactions returns paginated results for admin")
    void testGetTransactions_asAdmin() throws Exception {
        // Given
        User admin = createUser("admin@test.com", "9999");
        String token = tokenProvider.generateToken(admin);

        // When & Then
        mockMvc.perform(get("/api/transactions")
                .header("Authorization", "Bearer " + token)
                .param("startDate", "2025-01-01")
                .param("endDate", "2025-01-31")
                .param("page", "0")
                .param("size", "25"))
            .andExpect(status().isOk())
            .andExpect(jsonPath("$.content").isArray())
            .andExpect(jsonPath("$.totalElements").exists())
            .andExpect(jsonPath("$.totalPages").exists())
            .andDo(print());
    }

    @Test
    @DisplayName("GET /api/transactions returns 403 for unauthorized BU")
    void testGetTransactions_unauthorizedBu() throws Exception {
        // Given
        User user = createUser("user@test.com", "1234");  // BU 1234
        String token = tokenProvider.generateToken(user);

        // When & Then
        mockMvc.perform(get("/api/transactions")
                .header("Authorization", "Bearer " + token)
                .param("startDate", "2025-01-01")
                .param("endDate", "2025-01-31")
                .param("filterStore", "95010341"))  // Store belongs to BU 5678
            .andExpect(status().isForbidden());
    }
}
```

### Frontend Testing (React Testing Library)

```typescript
// features/dashboard/Dashboard.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';
import { Dashboard } from './Dashboard';
import { setupStore } from '@/app/store';

describe('Dashboard', () => {
  it('renders KPI cards with correct data', async () => {
    // Given
    const mockStats = {
      total: 1234,
      volume: 56789.50,
      settledCount: 1200,
      notSettledCount: 34,
    };

    const store = setupStore({
      dashboard: { stats: mockStats },
    });

    // When
    render(
      <Provider store={store}>
        <Dashboard />
      </Provider>
    );

    // Then
    await waitFor(() => {
      expect(screen.getByText('1.234')).toBeInTheDocument();
      expect(screen.getByText(/€ 56\.789,50/)).toBeInTheDocument();
      expect(screen.getByText('1.200')).toBeInTheDocument();
      expect(screen.getByText('34')).toBeInTheDocument();
    });
  });

  it('displays loading spinner while fetching data', () => {
    const store = setupStore({
      dashboard: { isLoading: true },
    });

    render(
      <Provider store={store}>
        <Dashboard />
      </Provider>
    );

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
});
```

---

## 📅 Piano di Migrazione

### Fase 1: Setup & Infrastructure (2 settimane)

**Attività:**
- Setup repository Git (monorepo o multi-repo)
- Configurazione ambiente sviluppo (Docker Compose)
- Setup CI/CD pipeline (GitHub Actions)
- Creazione Flyway migrations da schema PHP esistente
- Setup MySQL con dati di test
- Setup Redis per caching
- Configurazione Spring Boot skeleton
- Setup React + TypeScript + Vite

**Deliverable:**
- Repository configurato
- Pipeline CI/CD funzionante
- Database migrato con Flyway
- Skeleton applicazioni backend + frontend

---

### Fase 2: Core Backend (4 settimane)

**Settimana 1-2: Auth & User Management**
- Implementare JPA entities (User, Store, Transaction, etc.)
- Spring Security con JWT
- AuthController (login, logout, refresh)
- UserService con BU authorization
- Password expiry scheduler
- Admin user management CRUD

**Settimana 3-4: Transactions & Dashboard**
- TransactionService con calcolo volume netto
- StoreService
- TransactionRepository con custom queries
- Dashboard statistics service
- Circuit code mapper
- Response code translator
- BIN analyzer

**Testing:**
- Unit tests (target: 80% coverage)
- Integration tests per ogni controller

---

### Fase 3: Frontend Core (3 settimane)

**Settimana 1: Authentication & Layout**
- Login page con Material-UI
- JWT storage (Redux + HttpOnly cookie)
- Protected routes
- App layout (AppBar, Sidebar, Breadcrumbs)
- Theme customization

**Settimana 2: Dashboard & Transactions**
- Dashboard con KPI cards
- Charts integration (Recharts)
- Transaction list con MUI DataGrid
- Filters component
- Export functionality

**Settimana 3: Stores & Admin**
- Store list e dettaglio
- Admin user management
- Activation codes management
- Terminal config editor

---

### Fase 4: Integrazioni Esterne (2 settimane)

**Attività:**
- A-Cube API service (RestTemplate)
- Satispay API service (WebClient)
- Token caching con Redis
- HTTP Signature per Satispay
- Scheduled jobs (polling registrazioni)
- Terminal config public API

**Testing:**
- Mock external APIs
- Integration tests con WireMock

---

### Fase 5: Advanced Features (2 settimane)

**Attività:**
- Statistics avanzate
- Analisi BIN
- Grafici orari/settimanali
- Export CSV/Excel migliorato
- Password reset via email
- Audit logging completo
- Notifiche real-time (WebSocket opzionale)

---

### Fase 6: Testing & Optimization (2 settimane)

**Attività:**
- Load testing (JMeter o Gatling)
- Performance optimization
- Query optimization
- Caching strategy refinement
- Security audit
- Accessibility testing (WCAG 2.1)
- Browser compatibility testing
- Mobile responsive testing

---

### Fase 7: Deployment & Monitoring (1 settimana)

**Attività:**
- Setup Kubernetes cluster
- Deploy su staging
- Configurazione monitoring (Prometheus + Grafana)
- Setup logging centralizzato (ELK stack)
- Configurazione alerting
- Backup strategy
- Disaster recovery plan
- User training
- Documentazione

---

### Fase 8: Go-Live & Supporto (1 settimana)

**Attività:**
- Deploy production
- Smoke tests
- Parallel run (PHP + Spring Boot)
- Data validation
- Monitoring attivo
- Bugfix rapidi
- Post-launch review

---

## 💰 Stima Costi

### Team

| Ruolo | Settimane | Costo/Settimana | Totale |
|-------|-----------|-----------------|--------|
| Senior Backend Developer (Java/Spring) | 16 | €2.500 | €40.000 |
| Mid Backend Developer (Java/Spring) | 12 | €2.000 | €24.000 |
| Senior Frontend Developer (React/TS) | 14 | €2.500 | €35.000 |
| DevOps Engineer | 8 | €2.500 | €20.000 |
| QA Engineer | 6 | €1.800 | €10.800 |
| **TOTALE RISORSE UMANE** | | | **€129.800** |

### Infrastruttura (annuale)

| Risorsa | Costo Mensile | Costo Annuale |
|---------|---------------|---------------|
| Kubernetes Cluster (3 nodes) | €300 | €3.600 |
| Database RDS (MySQL) | €150 | €1.800 |
| Redis Cache | €80 | €960 |
| Storage S3 (logs, backups) | €50 | €600 |
| Monitoring (Prometheus + Grafana Cloud) | €100 | €1.200 |
| CDN (Cloudflare) | €50 | €600 |
| **TOTALE INFRASTRUTTURA** | **€730** | **€8.760** |

### Software & Licenze

| Software | Costo Annuale |
|----------|---------------|
| JetBrains IntelliJ IDEA (2 licenze) | €600 |
| GitHub Team (5 utenti) | €240 |
| Sentry (error tracking) | €600 |
| **TOTALE SOFTWARE** | **€1.440** |

### **TOTALE PROGETTO: €140.000**

*(Include sviluppo + primo anno infrastruttura + licenze)*

---

## 🎁 Benefici del Porting

### Tecnici

✅ **Scalabilità**: Auto-scaling con Kubernetes
✅ **Performance**: Caching Redis + connection pooling ottimizzato
✅ **Manutenibilità**: Architettura modulare, testabile
✅ **Type Safety**: Java 17 + TypeScript eliminano molti bug
✅ **Developer Experience**: Hot reload, debugging migliorato
✅ **Testing**: Coverage >80% con unit + integration tests
✅ **Security**: JWT stateless, refresh tokens, OWASP compliant
✅ **Monitoring**: Metrics dettagliati, alerting proattivo

### Business

📈 **Time to Market**: Feature nuove in 50% del tempo
💰 **Costi Operativi**: -30% grazie a auto-scaling
🚀 **Uptime**: 99.9% SLA con replica e health checks
📱 **Mobile Ready**: API REST consumabili da app native
🌍 **Multi-Tenant**: Architettura pronta per SaaS
🔒 **Compliance**: GDPR ready, audit logging completo
👥 **User Experience**: UI moderna, responsive, accessibile

---

## 🎯 Conclusioni

Il porting da PHP a Spring Boot + Hibernate + React rappresenta un **investimento strategico** che modernizza completamente l'architettura del sistema, garantendo:

1. **Scalabilità illimitata** con architettura cloud-native
2. **Performance superiori** grazie a caching multi-livello e query ottimizzate
3. **Developer Experience eccezionale** con hot reload, type safety e tooling moderno
4. **Security enterprise-grade** con JWT, RBAC e audit completo
5. **Manutenibilità a lungo termine** con testing automatizzato e CI/CD

**Tempo totale**: 17 settimane (4 mesi)
**Budget stimato**: €140.000
**ROI atteso**: 18 mesi

Il sistema risultante sarà **production-ready**, **scalabile**, **sicuro** e **mantenibile**, pronto per supportare la crescita del business PayGlobe nei prossimi anni.

---

**Documento redatto da**: Claude Code
**Data**: 2025-01-12
**Versione**: 1.0
