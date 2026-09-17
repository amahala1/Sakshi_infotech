/**
 * Sakshi Infotech - Admin & Multi-Role Staff Scripts
 */

document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss alerts
  document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    }, 4000);
  });
});

function openPaymentVerifyModal(orderId, orderNo, amount, ref, proofUrl) {
  document.getElementById('modalOrderId').value = orderId;
  document.getElementById('modalOrderNo').textContent = orderNo;
  document.getElementById('modalOrderAmount').textContent = '₹' + amount;
  document.getElementById('modalOrderRef').textContent = ref || 'None specified';
  
  const proofContainer = document.getElementById('modalProofContainer');
  if (proofUrl) {
    const cleanProof = proofUrl.startsWith('/') ? proofUrl.substring(1) : proofUrl;
    proofContainer.innerHTML = `<a href="../${cleanProof}" target="_blank" class="btn-sm-action btn-action-primary"><i class="fas fa-eye"></i> View Customer Payment Slip</a>`;
  } else {
    proofContainer.innerHTML = `<span class="text-muted" style="font-size:0.85rem;">No screenshot attached (Ref entry only)</span>`;
  }

  document.getElementById('paymentModal').style.display = 'flex';
}

function openDispatchModal(orderId, orderNo) {
  document.getElementById('dispatchOrderId').value = orderId;
  document.getElementById('dispatchOrderNo').textContent = orderNo;
  document.getElementById('dispatchModal').style.display = 'flex';
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
  }
}
