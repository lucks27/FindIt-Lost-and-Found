import api from '@/bootstrap'

export default {
  fetchReports(params = {}) {
    return api.get('/api/reports', { params })
  },

  fetchReport(id) {
    return api.get(`/api/reports/${id}`)
  },

  fetchMatches(id) {
    return api.get(`/api/reports/${id}/matches`)
  },

  confirmMatch(reportId, matchId) {
    return api.post(`/api/reports/${reportId}/matches/${matchId}/confirm`)
  },

  markReceived(reportId, payload = {}) {
    return api.post(`/api/reports/${reportId}/mark-received`, payload)
  },

  updateReport(reportId, payload = {}) {
    return api.put(`/api/reports/${reportId}`, payload)
  },
}
import api from '@/bootstrap'

export default {
  fetchReports(params = {}) {
    return api.get('/api/reports', { params })
  },

  fetchReport(id) {
    return api.get(`/api/reports/${id}`)
  },

  fetchMatches(id) {
    return api.get(`/api/reports/${id}/matches`)
  },

  confirmMatch(reportId, matchId) {
    return api.post(`/api/reports/${reportId}/matches/${matchId}/confirm`)
  },

  markReceived(reportId, payload = {}) {
    // Endpoint may be implemented server-side as needed
    return api.post(`/api/reports/${reportId}/mark-received`, payload)
  },

  updateReport(reportId, payload = {}) {
    return api.put(`/api/reports/${reportId}`, payload)
  },
}
