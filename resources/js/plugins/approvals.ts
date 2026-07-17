function canEditByApproval(approvalsMap, kind) {
  if (!approvalsMap) {
    return true; // no hay aprobaciones aún
  }

  const status = approvalsMap[kind];

  if (!status) {
    return true; // no existe ese kind en el mapa
  }

  if (status === "rejected") {
    return true; // rechazado => puede volver a modificar
  }

  if (status === "approved") {
    return false; // aprobado => ya no se puede modificar
  }

  // cualquier otro estado (ej. "pending")
  return true;
}