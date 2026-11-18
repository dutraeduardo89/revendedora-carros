import { Routes } from '@angular/router';
import { LoginComponent } from './login/login';
import { DashboardComponent } from './dashboard/dashboard';
import { CarFormComponent } from './car-form/car-form';
import { CadastrosComponent } from './cadastros/cadastros';
import { SettingsComponent } from './settings/settings';
import { RegisterComponent } from './register/register';
import { AuthGuard } from './guards/auth.guard';

export const routes: Routes = [
  { path: '', component: LoginComponent },
  { path: 'register', component: RegisterComponent },
  { path: 'dashboard', component: DashboardComponent, canActivate: [AuthGuard] },
  { path: 'cadastrar', component: CarFormComponent, canActivate: [AuthGuard] },
  { path: 'cadastros', component: CadastrosComponent, canActivate: [AuthGuard] },
  { path: 'settings', component: SettingsComponent, canActivate: [AuthGuard] }
];
