import axios from 'axios';

export async function getWorkshopCatalogsApi() {
    const response = await axios.get('maintenance-new/catalogs');

    return response.data;
}
