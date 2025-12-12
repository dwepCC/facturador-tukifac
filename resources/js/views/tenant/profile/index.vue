<template>
  <div class="container-fluid py-4">
            <div class="page-header pe-0">
        <div class="d-flex align-items-center" style="padding-left: 10px;">
          <i class="fas fa-user-circle fa-2x text-primary"></i>
          <h2 class="mb-0">Perfil de Usuario</h2>
        </div>

        </div>
    <!-- Header con título -->
    <div class="row mb-4">
      <div class="col-12">
        <h6 class="text-muted mb-0">Información personal, plan de suscripción y certificados</h6>
      </div>
    </div>

    <!-- Primera fila de tarjetas principales -->
    <div class="row g-4 mb-4">
      <!-- Tarjeta de perfil de usuario -->
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-user me-2"></i>
            <h5 class="mb-0">Perfil del Usuario</h5>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-user fa-lg"></i>
              </div>
              <div>
                <h4 class="mb-1">{{ user.name || 'Nombre no disponible' }}</h4>
                <p class="text-muted mb-0">{{ user.type || 'Tipo no especificado' }}</p>
              </div>
            </div>
            
            <div class="profile-info">
              <div class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-info bg-opacity-10 text-info me-3">
                  <i class="fas fa-envelope"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Email</small>
                  <strong>{{ user.email || 'No especificado' }}</strong>
                </div>
              </div>
              
              <div class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-success bg-opacity-10 text-success me-3">
                  <i class="fas fa-user-tag"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Tipo de usuario</small>
                  <strong>{{ user.type || 'No especificado' }}</strong>
                </div>
              </div>
            </div>
            
            <!--<div class="mt-4">
              <button class="btn btn-outline-primary btn-sm w-100">
                <i class="fas fa-edit me-1"></i> Editar perfil
              </button>
            </div>-->
          </div>
        </div>
      </div>

      <!-- Tarjeta de información del plan -->
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-info text-white d-flex align-items-center">
            <i class="fas fa-crown me-2"></i>
            <h5 class="mb-0">Plan de Suscripción</h5>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-crown fa-lg"></i>
              </div>
              <div>
                <h4 class="mb-1">{{ plan.plan_name || 'Sin plan' }}</h4>
                <span :class="planStatusClass" class="badge">{{ plan.status_plan || 'No especificado' }}</span>
              </div>
            </div>
            
            <div class="plan-info">
              <div class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-warning bg-opacity-10 text-warning me-3">
                  <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Fecha de pago</small>
                  <strong>{{ plan.payment_date || 'No especificada' }}</strong>
                </div>
              </div>
              
              <!-- Días de atraso o restantes -->
              <div v-if="plan.days_overdue > 0" class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-danger bg-opacity-10 text-danger me-3">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Días de atraso</small>
                  <strong class="text-danger">{{ plan.days_overdue }} días</strong>
                </div>
              </div>
              
              <div v-else-if="plan.days_remaining > 0" class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-success bg-opacity-10 text-success me-3">
                  <i class="fas fa-clock"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Días restantes</small>
                  <strong class="text-success">{{ plan.days_remaining }} días</strong>
                </div>
              </div>
              
              <div v-else class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-secondary bg-opacity-10 text-secondary me-3">
                  <i class="fas fa-info-circle"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Estado del plan</small>
                  <strong>Sin información de días</strong>
                </div>
              </div>
            </div>
            
            <!---<div class="mt-4">
              <button class="btn btn-outline-info btn-sm w-100">
                <i class="fas fa-eye me-1"></i> Ver detalles del plan
              </button>
            </div>-->
          </div>
        </div>
      </div>

      <!-- Tarjeta de certificado SUNAT -->
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header" :class="certificateHeaderClass">
            <i class="fas fa-file-certificate me-2"></i>
            <h5 class="mb-0">Certificado SUNAT</h5>
          </div>
          <div class="card-body">
            <div class="d-flex align-items-center mb-4">
              <div class="avatar-circle d-flex align-items-center justify-content-center me-3" :class="certificateIconClass">
                <i class="fas fa-file-certificate fa-lg"></i>
              </div>
              <div>
                <h4 class="mb-1">Certificado Digital</h4>
                <span class="badge" :class="certificateBadgeClass">
                  {{ daysRemaining === '-' ? 'Sin fecha' : (daysRemaining <= 0 ? 'Vencido' : 'Vigente') }}
                </span>
              </div>
            </div>
            
            <div class="certificate-info">
              <div class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle bg-primary bg-opacity-10 text-primary me-3">
                  <i class="fas fa-calendar-times"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Fecha de vencimiento</small>
                  <strong>{{ company.certificate_due || 'No registrada' }}</strong>
                </div>
              </div>
              
              <div class="info-item d-flex align-items-center mb-3">
                <div class="icon-circle me-3" :class="daysRemainingIconClass" >
                  <i class="fas fa-hourglass-half"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Días restantes</small>
                  <strong :style="{'color': expiryColor, 'font-weight': 'bold'}">
                    {{ daysRemaining === '-' ? 'Sin fecha' : (daysRemaining <= 0 ? 'Vencido' : daysRemaining + ' días') }}
                  </strong>
                </div>
              </div>
            </div>
            
            <!--<div class="mt-4">
              <button class="btn btn-outline-warning btn-sm w-100">
                <i class="fas fa-upload me-1"></i> Actualizar certificado
              </button>
            </div>-->
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de pagos -->
    <!---<div class="row">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-success text-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <i class="fas fa-receipt me-2"></i>
              <h5 class="mb-0">Historial de Pagos</h5>
            </div>
            <span class="badge bg-light text-dark">{{ payments.length }} registros</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-4">
                      <i class="fas fa-tag me-1"></i> Concepto
                    </th>
                    <th>
                      <i class="fas fa-calendar me-1"></i> Fecha Programada
                    </th>
                    <th>
                      <i class="fas fa-calendar-check me-1"></i> Fecha de Pago
                    </th>
                    <th>
                      <i class="fas fa-money-bill-wave me-1"></i> Monto
                    </th>
                    <th>
                      <i class="fas fa-check-circle me-1"></i> Estado
                    </th>
                    <th class="text-end pe-4">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in payments" :key="p.id" class="align-middle">
                    <td class="ps-4">
                      <div class="d-flex align-items-center">
                        <div :class="p.state ? 'payment-icon-paid' : 'payment-icon-pending'" class="d-flex align-items-center justify-content-center me-3">
                          <i :class="p.state ? 'fas fa-check' : 'fas fa-clock'"></i>
                        </div>
                        <div>
                          <strong>{{ p.description || 'Sin descripción' }}</strong>
                          <br>
                          <small class="text-muted">ID: {{ p.id || 'N/A' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">
                        <i class="fas fa-calendar me-1"></i>
                        {{ p.date_of_payment || 'No programada' }}
                      </span>
                    </td>
                    <td>
                      <span v-if="p.date_of_payment_real" class="badge bg-info text-white">
                        <i class="fas fa-calendar-check me-1"></i>
                        {{ p.date_of_payment_real }}
                      </span>
                      <span v-else class="badge bg-secondary text-white">
                        <i class="fas fa-calendar-times me-1"></i>
                        Sin pagar
                      </span>
                    </td>
                    <td>
                      <span class="fw-bold text-success">S/ {{ p.amount || '0.00' }}</span>
                    </td>
                    <td>
                      <span v-if="p.state" class="badge bg-success">
                        <i class="fas fa-check me-1"></i> Pagado
                      </span>
                      <span v-else class="badge bg-warning text-dark">
                        <i class="fas fa-clock me-1"></i> Pendiente
                      </span>
                    </td>
                    <td class="text-end pe-4">
                      <button v-if="!p.state" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-dollar-sign me-1"></i> Pagar
                      </button>
                      <button class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye me-1"></i> Ver
                      </button>
                    </td>
                  </tr>
                  <tr v-if="payments.length === 0">
                    <td colspan="6" class="text-center py-5">
                      <div class="text-muted">
                        <i class="fas fa-receipt fa-3x mb-3"></i>
                        <h5>No hay pagos registrados</h5>
                        <p>No se han encontrado registros de pagos para este usuario.</p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer text-end bg-light">
            <button class="btn btn-outline-success btn-sm">
              <i class="fas fa-plus me-1"></i> Nuevo pago
            </button>
            <button class="btn btn-outline-primary btn-sm ms-2">
              <i class="fas fa-download me-1"></i> Exportar
            </button>
          </div>
        </div>
      </div>
    </div>-->
  </div>
</template>

<script>
export default {
  name: 'TenantProfileIndex',
  data() {
    return {
      user: { name: '', email: '', type: '' },
      plan: { plan_name: '', status_plan: '', payment_date: '', days_overdue: 0, days_remaining: 0 },
      //payments: [],
      company: { certificate_due: null }
    }
  },
  created() {
    this.$http.get('/profile/record').then(r => { if (r.data && r.data.user) this.user = r.data.user })
    this.$http.get('/cuenta/info_plan').then(r => { if (r.data && r.data.success) this.plan = r.data })
    //this.$http.get('/cuenta/payment_records').then(r => { if (r.data && r.data.data) this.payments = r.data.data })
    this.$http.get('/companies/record').then(r => { if (r.data) this.company = r.data })
  },
  computed: {
    daysRemaining() {
      if (!this.company.certificate_due) return '-'
      const end = new Date(this.company.certificate_due + 'T00:00:00')
      const now = new Date()
      const diff = Math.ceil((end - now) / (1000*60*60*24))
      return diff
    },
    expiryColor() {
      const d = this.daysRemaining
      if (d === '-') return '#666'
      if (d <= 0) return '#dc3545' // rojo para vencido
      if (d <= 7) return '#dc3545' // rojo
      if (d <= 30) return '#ffc107' // amarillo
      return '#28a745' // verde
    },
    planStatusClass() {
      const status = this.plan.status_plan ? this.plan.status_plan.toLowerCase() : ''
      if (status.includes('activo') || status.includes('active')) return 'badge bg-success'
      if (status.includes('vencido') || status.includes('expired')) return 'badge bg-danger'
      if (status.includes('pendiente') || status.includes('pending')) return 'badge bg-warning'
      return 'badge bg-secondary'
    },
    certificateHeaderClass() {
      const d = this.daysRemaining
      if (d === '-') return 'bg-secondary text-white'
      if (d <= 0) return 'bg-danger text-white'
      if (d <= 7) return 'bg-danger text-white'
      if (d <= 30) return 'bg-warning text-dark'
      return 'bg-success text-white'
    },
    certificateIconClass() {
      const d = this.daysRemaining
      if (d === '-') return 'bg-secondary bg-opacity-10 text-secondary'
      if (d <= 0) return 'bg-danger bg-opacity-10 text-danger'
      if (d <= 7) return 'bg-danger bg-opacity-10 text-danger'
      if (d <= 30) return 'bg-warning bg-opacity-10 text-warning'
      return 'bg-success bg-opacity-10 text-success'
    },
    certificateBadgeClass() {
      const d = this.daysRemaining
      if (d === '-') return 'bg-secondary'
      if (d <= 0) return 'bg-danger'
      if (d <= 7) return 'bg-danger'
      if (d <= 30) return 'bg-warning text-dark'
      return 'bg-success'
    },
    daysRemainingIconClass() {
      const d = this.daysRemaining
      if (d === '-') return 'bg-secondary bg-opacity-10 text-secondary'
      if (d <= 0) return 'bg-danger bg-opacity-10 text-danger'
      if (d <= 7) return 'bg-danger bg-opacity-10 text-danger'
      if (d <= 30) return 'bg-warning bg-opacity-10 text-warning'
      return 'bg-success bg-opacity-10 text-success'
    }
  }
}
</script>

<style scoped>
.avatar-circle {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
}

.icon-circle {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.payment-icon-paid {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgba(40, 167, 69, 0.1);
  color: #28a745;
  display: flex;
  align-items: center;
  justify-content: center;
}

.payment-icon-pending {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: rgba(255, 193, 7, 0.1);
  color: #ffc107;
  display: flex;
  align-items: center;
  justify-content: center;
}

.card {
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
}

.card-header {
  border-bottom: none;
  font-weight: 600;
  padding: 1rem 1.5rem;
}

.card-body {
  padding: 1.5rem;
}

.table th {
  font-weight: 600;
  border-top: none;
  padding: 1rem;
}

.table td {
  padding: 1rem;
  vertical-align: middle;
}

.info-item {
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.badge {
  font-size: 0.8rem;
  padding: 0.35rem 0.65rem;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}
</style>