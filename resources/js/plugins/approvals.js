export function approved(approvalsMap, kind) {
  if (!approvalsMap) {
    return true; 
  }

  const status = approvalsMap[kind];

  if (!status) {
    return true;
  }

  if (status === "rejected") {
    return true; 
  }

  if (status === "approved") {
    return false; 
  }

  if(status === "pending") {
    return false;
  }

  return false;
}